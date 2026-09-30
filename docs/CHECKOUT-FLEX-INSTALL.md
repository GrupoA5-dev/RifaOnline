# Instalação — Patch 0.4.2

## 1. Backup
Faça backup do banco `grupoaco_rifas` antes da migration.

## 2. Extrair
Extraia o ZIP diretamente em:

`/home/grupoaco/rifas-a5-app/`

O patch não contém `.env`, `index.php` público nem `.htaccess` público.

## 3. Atualizar autoload
```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
```

## 4. Migrations
```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
/opt/alt/php84/usr/bin/php artisan migrate --force
```

Novas migrations:
- `2026_09_12_020100_add_checkout_fields_to_raffles_table`
- `2026_09_12_020200_make_customer_phone_nullable`

## 5. Versão
```bash
sed -i 's/^APP_VERSION=.*/APP_VERSION=0.4.2/' .env
```

## 6. Build frontend
```bash
export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH
npm run build
```

Se `node_modules` não existir, execute `npm install` antes do build.

## 7. Publicar assets
Preserve os arquivos públicos personalizados:

```bash
cp /home/grupoaco/rifas.meu.ink/sistema/index.php /home/grupoaco/index-checkout-flex.php
cp /home/grupoaco/rifas.meu.ink/sistema/.htaccess /home/grupoaco/htaccess-checkout-flex
cp -a /home/grupoaco/rifas-a5-app/public/. /home/grupoaco/rifas.meu.ink/sistema/
cp /home/grupoaco/index-checkout-flex.php /home/grupoaco/rifas.meu.ink/sistema/index.php
cp /home/grupoaco/htaccess-checkout-flex /home/grupoaco/rifas.meu.ink/sistema/.htaccess
```

## 8. Limpar cache
```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

## 9. Validar
```bash
/opt/alt/php84/usr/bin/php artisan a5:checkout-flex-status
```

Resultado esperado:

```text
raffle_collect_fields       OK
customer_phone_nullable     OK
numbers_route               OK
selected_action             OK
numbers_controller          OK

Checkout configurável e escolha manual instalados corretamente.
```

## 10. Usar no painel
Em **Admin > Campanhas > Editar**:
- Distribuição dos números: `Escolha manual` ou `Números aleatórios`.
- Ative/desative individualmente:
  - Solicitar nome completo
  - Solicitar e-mail
  - Solicitar WhatsApp / telefone
  - Solicitar CPF

Quando `Escolha manual` estiver ativa, a página pública exibirá a grade de números.
