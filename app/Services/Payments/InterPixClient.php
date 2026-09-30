<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class InterPixClient
{
    public function __construct(private readonly InterPixSettings $settings) {}

    public function environment(): string
    {
        return (string) $this->settings->get('environment', 'sandbox');
    }

    public function baseUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://cdpj.partners.bancointer.com.br'
            : 'https://cdpj-sandbox.partners.uatinter.co';
    }

    /** @return array{expires_in:int,scope:string,token_type:string} */
    public function testAuthentication(array $scopes = ['cob.read']): array
    {
        $token = $this->requestAccessToken($scopes, cache: false);

        return [
            'expires_in' => (int) ($token['expires_in'] ?? 0),
            'scope' => (string) ($token['scope'] ?? implode(' ', $scopes)),
            'token_type' => (string) ($token['token_type'] ?? 'Bearer'),
        ];
    }

    public function get(string $uri, array $query, array $scopes): Response
    {
        return $this->authorizedRequest($scopes)->get($this->baseUrl().$uri, $query);
    }

    public function put(string $uri, array $payload, array $scopes): Response
    {
        return $this->authorizedRequest($scopes)->put($this->baseUrl().$uri, $payload);
    }

    public function delete(string $uri, array $scopes): Response
    {
        return $this->authorizedRequest($scopes)->delete($this->baseUrl().$uri);
    }

    public function assertSuccessful(Response $response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        $message = trim((string) ($response->json('detail')
            ?? $response->json('title')
            ?? $response->json('message')
            ?? $response->body()));

        $message = mb_substr($message, 0, 600);

        throw new RuntimeException(sprintf(
            'Banco Inter retornou HTTP %d em %s%s',
            $response->status(),
            $context,
            $message !== '' ? ': '.$message : '.',
        ));
    }

    private function authorizedRequest(array $scopes): PendingRequest
    {
        $token = $this->accessToken($scopes);
        $request = Http::withOptions($this->tlsOptions())
            ->acceptJson()
            ->asJson()
            ->withToken($token)
            ->connectTimeout(12)
            ->timeout(25)
            ->retry(1, 250, throw: false);

        // A API Pix do Inter não exige x-conta-corrente para as operações de cobrança/webhook.
        // O header x-conta-corrente é informado pelo Inter nos callbacks para identificar a conta
        // de origem. Enviá-lo nas requisições de saída pode provocar 401 quando o valor não corresponde
        // exatamente à conta associada à integração.
        return $request;
    }

    private function accessToken(array $scopes): string
    {
        sort($scopes);
        $cacheKey = 'inter-pix-token:'.sha1($this->environment().'|'.(string) $this->settings->get('client_id').'|'.implode(' ', $scopes));
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $payload = $this->requestAccessToken($scopes, cache: true);
        $token = (string) ($payload['access_token'] ?? '');

        if ($token === '') {
            throw new RuntimeException('O Banco Inter não retornou access_token no OAuth.');
        }

        $ttl = max(60, ((int) ($payload['expires_in'] ?? 3600)) - 300);
        Cache::put($cacheKey, $token, now()->addSeconds($ttl));

        return $token;
    }

    private function requestAccessToken(array $scopes, bool $cache): array
    {
        $status = $this->settings->configurationStatus();
        if (! $status['ready']) {
            throw new RuntimeException('A configuração do Pix Banco Inter está incompleta.');
        }

        $response = Http::withOptions($this->tlsOptions())
            ->acceptJson()
            ->asForm()
            ->connectTimeout(12)
            ->timeout(25)
            ->post($this->baseUrl().'/oauth/v2/token', [
                'client_id' => (string) $this->settings->get('client_id'),
                'client_secret' => (string) $this->settings->getSecret('client_secret'),
                'grant_type' => 'client_credentials',
                'scope' => implode(' ', $scopes),
            ]);

        $this->assertSuccessful($response, 'autenticação OAuth');

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Resposta OAuth do Banco Inter em formato inesperado.');
        }

        return $data;
    }

    /** @return array<string,mixed> */
    private function tlsOptions(): array
    {
        $mode = (string) $this->settings->get('certificate_mode', 'pem');

        if ($mode === 'pfx') {
            [$certificate, $privateKey] = $this->extractPfxRuntimePair();

            return [
                'cert' => $certificate,
                'ssl_key' => $privateKey,
                'verify' => true,
            ];
        }

        $certificate = $this->settings->absoluteFilePath('certificate_crt_path');
        $privateKey = $this->settings->absoluteFilePath('private_key_path');

        if (! $certificate || ! $privateKey) {
            throw new RuntimeException('Certificado CRT/PEM e chave privada do Banco Inter não foram encontrados.');
        }

        return [
            'cert' => $certificate,
            'ssl_key' => $privateKey,
            'verify' => true,
        ];
    }

    /** @return array{0:string,1:string} */
    private function extractPfxRuntimePair(): array
    {
        $pfxPath = $this->settings->absoluteFilePath('certificate_pfx_path');
        if (! $pfxPath || ! is_file($pfxPath)) {
            throw new RuntimeException('Arquivo PFX/P12 do Banco Inter não foi encontrado.');
        }

        $password = (string) ($this->settings->getSecret('certificate_pfx_password') ?? '');
        $fingerprint = sha1($pfxPath.'|'.(string) @filemtime($pfxPath));
        $dir = storage_path('app/secure/inter/runtime');
        $certificatePath = $dir.'/'.$fingerprint.'.crt.pem';
        $privateKeyPath = $dir.'/'.$fingerprint.'.key.pem';

        if (is_file($certificatePath) && is_file($privateKeyPath)) {
            return [$certificatePath, $privateKeyPath];
        }

        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException('Não foi possível criar o diretório temporário do certificado do Banco Inter.');
        }

        $contents = @file_get_contents($pfxPath);
        $certificates = [];
        if (! is_string($contents) || ! function_exists('openssl_pkcs12_read') || ! @openssl_pkcs12_read($contents, $certificates, $password)) {
            throw new RuntimeException('Não foi possível abrir o certificado PFX/P12 do Banco Inter.');
        }

        $certificate = (string) ($certificates['cert'] ?? '');
        foreach (($certificates['extracerts'] ?? []) as $extra) {
            $certificate .= "\n".$extra;
        }
        $privateKey = (string) ($certificates['pkey'] ?? '');

        if ($certificate === '' || $privateKey === '') {
            throw new RuntimeException('O PFX/P12 não contém certificado e chave privada utilizáveis.');
        }

        file_put_contents($certificatePath, $certificate, LOCK_EX);
        file_put_contents($privateKeyPath, $privateKey, LOCK_EX);
        @chmod($certificatePath, 0600);
        @chmod($privateKeyPath, 0600);

        return [$certificatePath, $privateKeyPath];
    }
}
