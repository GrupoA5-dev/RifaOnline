# Deploy de staging

1. PHP 8.4 com extensões comuns do Laravel: mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, fileinfo, curl.
2. Criar banco MySQL/MariaDB dedicado.
3. Apontar o domínio/subdomínio para a pasta `public/` do projeto.
4. Copiar `.env.example` para `.env` e preencher credenciais.
5. Definir `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://...`.
6. Definir `ADMIN_EMAIL` e `ADMIN_PASSWORD` antes do primeiro seed.
7. Rodar:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
npm ci
npm run build
php artisan storage:link
php artisan optimize
```

8. Cron (1 minuto):

```cron
* * * * * cd /CAMINHO/DO/PROJETO && php artisan schedule:run >> /dev/null 2>&1
```

9. Worker permanente (Supervisor/systemd/painel da hospedagem):

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

10. Validar `/health`, `/admin` e `/`.
