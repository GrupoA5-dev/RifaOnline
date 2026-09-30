# Instalação — WORK 6

1. Faça backup do banco `grupoaco_rifas`.
2. Extraia o patch diretamente em `/home/grupoaco/rifas-a5-app/`.
3. Atualize o autoload:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
```

4. Confira as migrations:

```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
```

Devem aparecer como `Pending`:
- `2026_09_12_040100_add_public_token_to_customers_table`
- `2026_09_12_040200_create_raffle_winners_table`

5. Execute:

```bash
/opt/alt/php84/usr/bin/php artisan migrate --force
```

6. Atualize a versão:

```bash
sed -i 's/^APP_VERSION=.*/APP_VERSION=0.6.0/' .env
```

7. Compile o frontend:

```bash
export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH
npm run build
```

8. Preserve `index.php` e `.htaccess` públicos:

```bash
cp /home/grupoaco/rifas.meu.ink/sistema/index.php /home/grupoaco/index-work6.php
cp /home/grupoaco/rifas.meu.ink/sistema/.htaccess /home/grupoaco/htaccess-work6
```

9. Publique os assets:

```bash
cp -a /home/grupoaco/rifas-a5-app/public/. /home/grupoaco/rifas.meu.ink/sistema/
cp /home/grupoaco/index-work6.php /home/grupoaco/rifas.meu.ink/sistema/index.php
cp /home/grupoaco/htaccess-work6 /home/grupoaco/rifas.meu.ink/sistema/.htaccess
```

10. Limpe caches:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

11. Valide:

```bash
/opt/alt/php84/usr/bin/php artisan a5:work6-status
```

Resultado esperado:

```text
customer_public_token         OK
raffle_winners                OK
my_numbers_lookup_route       OK
my_numbers_show_route         OK
winner_resource               OK
customer_edit_page            OK

WORK 6 instalado corretamente.
```
