# WORK 3 — PIX / MERCADO PAGO

## Projeto
APP-2026-001-RIFAS-A5

## Objetivo
Adicionar o núcleo financeiro do sistema:

- geração de Pix Mercado Pago;
- idempotência;
- validação de valor e referência;
- webhook assinado;
- conciliação automática;
- pagamentos e eventos no Filament;
- proteção contra liberar números quando o gateway está incerto.

## Antes de instalar

1. Faça backup do banco `grupoaco_rifas`.
2. Confirme que o WORK 2 está funcionando.
3. Não envie `MERCADOPAGO_ACCESS_TOKEN` ou `MERCADOPAGO_WEBHOOK_SECRET` por chat, print ou commit.
4. Não execute `migrate:fresh`.

## 1. Extrair o patch

Extraia o ZIP diretamente em:

`/home/grupoaco/rifas-a5-app`

O patch não contém `.env` e não altera o `index.php` público de `/home/grupoaco/rifas.meu.ink/sistema`.

## 2. Atualizar autoload

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
```

## 3. Conferir migrations

```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
```

Devem aparecer três migrations novas como `Pending`:

- `add_document_to_customers_table`
- `create_payments_table`
- `create_payment_events_table`

Execute:

```bash
/opt/alt/php84/usr/bin/php artisan migrate --force
```

## 4. Configurar `.env`

Edite somente:

`/home/grupoaco/rifas-a5-app/.env`

Adicione:

```env
MERCADOPAGO_BASE_URL=https://api.mercadopago.com
MERCADOPAGO_ACCESS_TOKEN="COLOQUE_AQUI_NO_SERVIDOR"
MERCADOPAGO_WEBHOOK_SECRET="COLOQUE_AQUI_NO_SERVIDOR"
MERCADOPAGO_NOTIFICATION_URL=https://rifas.meu.ink/sistema/webhooks/mercadopago
MERCADOPAGO_TIMEOUT=15
MERCADOPAGO_CONNECT_TIMEOUT=5
```

Atualize também:

```env
APP_VERSION=0.3.0
```

## 5. Limpar caches

```bash
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

## 6. Validar instalação

```bash
/opt/alt/php84/usr/bin/php artisan a5:work3-status
```

Antes das credenciais, o código pode aparecer instalado com credenciais pendentes.
Depois da configuração, o esperado é:

`WORK 3 instalado e credenciais configuradas.`

## 7. Configurar Mercado Pago Developers

No painel da sua aplicação Mercado Pago:

- abra Webhooks;
- use modo de produção quando for trabalhar com credenciais reais;
- configure a URL:

`https://rifas.meu.ink/sistema/webhooks/mercadopago`

- ative notificações de pagamento (`payment` / Pagamentos) para esta integração;
- salve;
- copie a chave secreta gerada para `MERCADOPAGO_WEBHOOK_SECRET` no `.env`.

A chave secreta de Webhook é diferente do Access Token.

## 8. Scheduler

O WORK 3 adiciona conciliação periódica ao Scheduler do Laravel.
Se o cron do WORK 2 já estiver configurado, não crie outro.

Cron esperado no cPanel:

```bash
/opt/alt/php84/usr/bin/php /home/grupoaco/rifas-a5-app/artisan schedule:run >> /dev/null 2>&1
```

Periodicidade: uma vez por minuto.

## 9. Painel

Depois da instalação, `/sistema/admin` deverá mostrar também:

- Pagamentos
- Webhooks de pagamento

## 10. Teste de segurança do endpoint

Uma chamada sem assinatura para o webhook deve ser recusada com HTTP 401.
Isso é esperado.

```bash
curl -i -X POST https://rifas.meu.ink/sistema/webhooks/mercadopago \
  -H 'Content-Type: application/json' \
  -d '{}'
```

## Observação

O WORK 3 prepara o backend financeiro. O checkout visual Pix será conectado ao frontend no WORK 4.
