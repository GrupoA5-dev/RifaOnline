# Instalação — WORK 7 Pix automático Banco Inter

> Este patch NÃO contém `.env`, credenciais, certificados, `public/index.php` ou `.htaccess`.

## 1. Backup

Faça backup do banco e mantenha as cópias conhecidas do `index.php` e `.htaccess` públicos.

## 2. Extrair

Extraia o ZIP diretamente em:

`/home/grupoaco/rifas-a5-app/`

## 3. Autoload e configurações padrão

```bash
cd /home/grupoaco/rifas-a5-app

/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
/opt/alt/php84/usr/bin/php artisan db:seed --class=SystemSettingSeeder --force
```

Não há migration nova no WORK 7.

Atualize a versão sem regredir o sistema:

```bash
sed -i 's/^APP_VERSION=.*/APP_VERSION=0.11.0/' .env
```

## 4. Limpar caches

```bash
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

## 5. Recompilar somente o frontend

```bash
export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH
npm run build
```

Publique SOMENTE o diretório `build`. Não copie `public/index.php` nem `.htaccess`:

```bash
rm -rf /home/grupoaco/rifas.meu.ink/sistema/build
cp -a /home/grupoaco/rifas-a5-app/public/build /home/grupoaco/rifas.meu.ink/sistema/
```

## 6. Verificar instalação

```bash
/opt/alt/php84/usr/bin/php artisan a5:work7-status
/opt/alt/php84/usr/bin/php artisan inter:pix-test
```

O teste OAuth não cria cobrança e não movimenta dinheiro.

## 7. Cadastrar webhook

```bash
/opt/alt/php84/usr/bin/php artisan inter:webhook-register
/opt/alt/php84/usr/bin/php artisan inter:webhook-status
```

A URL usada será a exibida em Gestão > Configurações > Pix Banco Inter. O WORK 7 acrescenta um token aleatório ao caminho do webhook; use sempre a URL exibida pelo sistema e não tente montar a URL manualmente.

## 8. Confirmar scheduler

O cron principal deve continuar executando `schedule:run` uma vez por minuto:

```cron
* * * * * /opt/alt/php84/usr/bin/php /home/grupoaco/rifas-a5-app/artisan schedule:run >> /dev/null 2>&1
```

O scheduler agora executa também a conciliação Pix Inter a cada minuto.

## 9. Primeiro teste real recomendado

1. Em Gestão > Pix Banco Inter, confirme `Produção`, credenciais válidas e `Ativar Pix automático Banco Inter = ON`.
2. Deixe o prazo em 5 minutos.
3. Use uma campanha de teste com preço de R$ 1,00.
4. Faça uma compra de 1 número pelo site.
5. A tela deve mostrar QR Code/Copia e Cola do Banco Inter e a mensagem de confirmação automática.
6. Pague R$ 1,00 a partir de outra conta.
7. Em poucos segundos a tela deve mudar para `Pagamento confirmado!`.
8. Confirme no painel que pedido, pagamento e número estão como pagos.

## 10. Em caso de erro

Não reinstale o patch. Rode:

```bash
/opt/alt/php84/usr/bin/php artisan inter:pix-test
/opt/alt/php84/usr/bin/php artisan inter:webhook-status
/opt/alt/php84/usr/bin/php artisan payments:reconcile --limit=20
tail -n 100 storage/logs/laravel.log
```

Nunca envie Client Secret, chave privada, senha PFX ou access token no chat.
