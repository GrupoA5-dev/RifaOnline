# A5 Rifas — 2FA configurável

Este hotfix adiciona em **Gestão > Configurações > Operação e segurança** a opção:

**Autenticação em 2 fatores (2FA) no painel**

- ATIVA: 2FA obrigatório para o painel administrativo.
- DESATIVADA: o painel usa login/senha sem exigir TOTP.
- Ao desativar, segredos e códigos de recuperação já configurados são preservados.
- Ao reativar, administradores que já configuraram 2FA voltam a usar o autenticador normalmente.
- Somente SuperAdmin pode alterar esta opção.
- O padrão continua sendo ATIVO caso a configuração ainda não exista.

## Instalação

```bash
cd /home/grupoaco/rifas-a5-app
unzip -o APP-2026-001-RIFAS-A5_WORK11-2FA-TOGGLE_PATCH.zip -d /home/grupoaco/rifas-a5-app

/opt/alt/php84/usr/bin/php -l app/Providers/Filament/AdminPanelProvider.php
/opt/alt/php84/usr/bin/php -l app/Filament/Pages/OperationsSettingsPage.php
/opt/alt/php84/usr/bin/php -l app/Console/Commands/SecurityStatus.php

/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

Não há migration, build de frontend ou alteração de `public/index.php` / `.htaccess`.

## Uso

Abra **Admin > Configurações > Operação e segurança** e use o toggle de 2FA.

Após mudar a opção, é recomendável sair do painel e entrar novamente para validar o comportamento.

## Diagnóstico

```bash
/opt/alt/php84/usr/bin/php artisan a5:security-status
```

O comando mostrará se o 2FA está ATIVO ou DESATIVADO por configuração.
