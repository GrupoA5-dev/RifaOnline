# Instalação — WORK 11

> Faça backup do banco antes da instalação.

## 1. Extrair o patch

Na raiz `/home/grupoaco/rifas-a5-app`:

```bash
unzip -o APP-2026-001-RIFAS-A5_WORK11_SECURITY-FINAL_PATCH.zip -d /home/grupoaco/rifas-a5-app
```

## 2. Atualizar autoload e validar PHP antes de migrar

```bash
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
```


```bash
/opt/alt/php84/usr/bin/php -l app/Models/User.php
/opt/alt/php84/usr/bin/php -l app/Models/Customer.php
/opt/alt/php84/usr/bin/php -l app/Providers/Filament/AdminPanelProvider.php
/opt/alt/php84/usr/bin/php -l app/Http/Controllers/Public/MyNumbersLookupController.php
/opt/alt/php84/usr/bin/php -l app/Console/Commands/SecurityStatus.php
```

## 3. Rodar migrations

```bash
/opt/alt/php84/usr/bin/php artisan migrate --force
```

As migrations adicionam:
- `customers.access_pin` (criptografado pela aplicação)
- `users.app_authentication_secret`
- `users.app_authentication_recovery_codes`

Clientes existentes recebem automaticamente um PIN novo.

## 4. Atualizar versão e limpar caches

```bash
sed -i 's/^APP_VERSION=.*/APP_VERSION=1.0.0/' .env
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

## 5. Compilar frontend

```bash
export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH
npm run build
```

Publique SOMENTE o build:

```bash
rm -rf /home/grupoaco/rifas.meu.ink/sistema/build
cp -a /home/grupoaco/rifas-a5-app/public/build /home/grupoaco/rifas.meu.ink/sistema/
```

NÃO copie a pasta `public` inteira. Não altere `index.php` nem `.htaccess`.

## 6. Configurar 2FA

Acesse `/sistema/admin/login` normalmente.

Após e-mail/senha, o Filament exigirá a configuração do aplicativo autenticador. Escaneie o QR Code com Google Authenticator, Microsoft Authenticator, Authy, 1Password ou equivalente.

Guarde os códigos de recuperação em local seguro. Cada administrador ativo precisa configurar o próprio 2FA.

## 7. Testar “Meus números”

Faça uma compra pequena. Na tela do Pix aparecerá o código de acesso de 6 dígitos.

Em `/sistema/meus-numeros`, informe:
- WhatsApp usado na compra
- Código de acesso de 6 dígitos

No painel, em Clientes, a ação “Novo código de acesso” permite regenerar o código quando o cliente perder o anterior.

## 8. Checagem final

```bash
/opt/alt/php84/usr/bin/php artisan a5:security-status
```

Logo após a instalação, `all_active_admins_have_mfa` pode aparecer PENDENTE. Configure 2FA para todos os admins e rode novamente.

Também rode:

```bash
/opt/alt/php84/usr/bin/php artisan a5:preflight
/opt/alt/php84/usr/bin/php artisan a5:work7-status
```

Se o scheduler aparecer pendente, confira o cron:

```text
* * * * * /opt/alt/php84/usr/bin/php /home/grupoaco/rifas-a5-app/artisan schedule:run >> /dev/null 2>&1
```
