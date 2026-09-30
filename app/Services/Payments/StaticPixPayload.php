<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;
use InvalidArgumentException;

final class StaticPixPayload
{
    public function make(
        string $key,
        string $merchantName,
        string $merchantCity,
        ?int $amountCents,
        string $txid,
    ): string {
        $key = trim($key);

        if ($key === '' || strlen($key) > 77) {
            throw new InvalidArgumentException('A chave Pix está vazia ou possui tamanho inválido.');
        }

        $merchantName = $this->normalizeText($merchantName, 25, 'nome do recebedor');
        $merchantCity = $this->normalizeText($merchantCity, 15, 'cidade do recebedor');
        $txid = $this->normalizeTxid($txid);

        $merchantAccount = $this->tlv('00', 'br.gov.bcb.pix')
            .$this->tlv('01', $key);

        $payload = $this->tlv('00', '01')
            .$this->tlv('26', $merchantAccount)
            .$this->tlv('52', '0000')
            .$this->tlv('53', '986');

        if ($amountCents !== null) {
            if ($amountCents <= 0) {
                throw new InvalidArgumentException('O valor do Pix deve ser maior que zero.');
            }

            $payload .= $this->tlv('54', number_format($amountCents / 100, 2, '.', ''));
        }

        $payload .= $this->tlv('58', 'BR')
            .$this->tlv('59', $merchantName)
            .$this->tlv('60', $merchantCity)
            .$this->tlv('62', $this->tlv('05', $txid))
            .'6304';

        return $payload.$this->crc16($payload);
    }

    public function txidForOrder(string $orderUuid): string
    {
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', (string) config('pix.txid_prefix', 'A5')) ?: 'A5';
        $prefix = substr($prefix, 0, 6);
        $uuid = preg_replace('/[^A-Za-z0-9]/', '', $orderUuid) ?: Str::random(24);
        $remaining = max(1, 25 - strlen($prefix));

        return substr($prefix.$uuid, 0, strlen($prefix) + $remaining);
    }

    private function normalizeTxid(string $txid): string
    {
        $txid = preg_replace('/[^A-Za-z0-9]/', '', $txid) ?? '';

        if ($txid === '') {
            return '***';
        }

        return substr($txid, 0, 25);
    }

    private function normalizeText(string $value, int $maxLength, string $field): string
    {
        $value = trim(Str::ascii($value));
        $value = preg_replace('/[^A-Za-z0-9 $%*+\-.\/:]/', '', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException("O {$field} é obrigatório para gerar o Pix.");
        }

        return substr($value, 0, $maxLength);
    }

    private function tlv(string $id, string $value): string
    {
        $length = strlen($value);

        if ($length > 99) {
            throw new InvalidArgumentException("Campo BR Code {$id} excede 99 caracteres.");
        }

        return $id.str_pad((string) $length, 2, '0', STR_PAD_LEFT).$value;
    }

    private function crc16(string $payload): string
    {
        $crc = 0xFFFF;

        for ($i = 0, $length = strlen($payload); $i < $length; $i++) {
            $crc ^= ord($payload[$i]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000)
                    ? (($crc << 1) ^ 0x1021)
                    : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
