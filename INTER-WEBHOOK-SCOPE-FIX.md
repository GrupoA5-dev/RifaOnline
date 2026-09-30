# Inter Webhook Scope Fix

Remove o fallback incorreto para os escopos `webhook-pix.write` e `webhook-pix.read`.
A integração validada pelo Banco Inter usa `webhook.write` e `webhook.read`.

Isso também evita mascarar o erro real retornado pelo endpoint de webhook.

## Instalação
Extraia na raiz de `/home/grupoaco/rifas-a5-app/` e execute:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php -l app/Services/Payments/InterPixGateway.php
/opt/alt/php84/usr/bin/php artisan optimize:clear
```

Depois teste:

```bash
/opt/alt/php84/usr/bin/php artisan inter:webhook-register
/opt/alt/php84/usr/bin/php artisan inter:webhook-status
```
