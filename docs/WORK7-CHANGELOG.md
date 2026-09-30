# WORK 7 — Pix automático Banco Inter

Versão alvo da aplicação após instalação: 0.11.0. O WORK 7 foi implementado agora sobre a base já evoluída até o WORK 10, sem regredir a versão do sistema.

## Entregas

- Integração mTLS + OAuth2 com Banco Inter usando as credenciais já salvas no painel.
- Suporte a certificado CRT/PEM + KEY/PEM e PFX/P12.
- Cache do access token para evitar geração de token a cada requisição.
- Criação de cobrança Pix imediata por `txid` usando `PUT /pix/v2/cob/{txid}`.
- Pix Copia e Cola retornado pelo Banco Inter e QR Code renderizado pelo frontend já existente.
- Prazo configurável de pagamento Inter; padrão de 5 minutos.
- Webhook em URL aleatória protegida (`/webhooks/inter/pix/{token}`), cadastrada automaticamente no Inter.
- Callback nunca é usado sozinho como prova de pagamento: o sistema consulta a cobrança no Inter antes de aprovar.
- Conciliação de contingência pelo navegador, limitada a uma consulta remota a cada 15 segundos por pagamento.
- Conciliação agendada a cada minuto.
- Conferência final antes de liberar números vencidos, com margem de segurança de 60 segundos.
- Em indisponibilidade do Banco Inter, números não são liberados automaticamente até ser possível confirmar o estado da cobrança.
- Comandos de diagnóstico e cadastro do webhook.
- Pix estático continua disponível quando o Pix Inter estiver desativado.

## Comandos

- `php artisan inter:pix-test`
- `php artisan inter:webhook-register`
- `php artisan inter:webhook-status`
- `php artisan payments:reconcile`
- `php artisan a5:work7-status`

## Segurança

- Client Secret continua criptografado pelo Laravel.
- Certificados permanecem em `storage/app/secure/inter`, fora da pasta pública.
- Webhook usa caminho secreto, é idempotente por `event_key` e o callback nunca confirma pagamento sem consulta ativa ao Inter.
- Um callback recebido só pode confirmar um pagamento depois de consulta ativa ao Banco Inter e validação de `txid` e valor.
- O `UNIQUE(raffle_id, number)` existente continua sendo a proteção final contra dupla venda de número.
