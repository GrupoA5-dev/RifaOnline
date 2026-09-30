# Instalação — WORK 10

Aplicação privada:

`/home/grupoaco/rifas-a5-app`

Pasta pública real:

`/home/grupoaco/rifas.meu.ink/sistema`

## 1. Backup

Faça backup do banco `grupoaco_rifas`.

Guarde novamente os dois arquivos públicos especiais:

```bash
cp /home/grupoaco/rifas.meu.ink/sistema/index.php /home/grupoaco/index-PRODUCAO-OK.php
cp /home/grupoaco/rifas.meu.ink/sistema/.htaccess /home/grupoaco/htaccess-PRODUCAO-OK
```

## 2. Manutenção

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php artisan down --retry=60
```

## 3. Extrair o patch

Extraia o ZIP diretamente dentro de:

`/home/grupoaco/rifas-a5-app/`

Depois:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
```

## 4. Banco

Confira:

```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
```

Devem aparecer 3 migrations novas:

- `2026_09_12_050100_add_contact_and_seo_to_raffles_table`
- `2026_09_12_050200_create_raffle_events_table`
- `2026_09_12_050300_add_archived_at_to_customers_table`

Execute:

```bash
/opt/alt/php84/usr/bin/php artisan migrate --force
/opt/alt/php84/usr/bin/php artisan db:seed --class=SystemSettingSeeder --force
```

O seeder usa `firstOrCreate` e não substitui as configurações já existentes.

## 5. Versão

```bash
sed -i 's/^APP_VERSION=.*/APP_VERSION=0.10.0/' .env
```

## 6. Frontend

```bash
export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH
npm run build
```

## 7. Publicação segura

**NÃO execute `cp -a public/. /sistema/`.**

Publique somente o build do Vite:

```bash
rm -rf /home/grupoaco/rifas.meu.ink/sistema/build
cp -a /home/grupoaco/rifas-a5-app/public/build /home/grupoaco/rifas.meu.ink/sistema/
```

Confirme que os arquivos especiais continuam corretos:

```bash
grep -nE "maintenance|vendor|bootstrap" /home/grupoaco/rifas.meu.ink/sistema/index.php
```

O `vendor` precisa apontar para:

`/home/grupoaco/rifas-a5-app/vendor/autoload.php`

## 8. Limpeza e validação

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
/opt/alt/php84/usr/bin/php artisan a5:work10-status
```

Esperado:

`WORK 10 instalado corretamente.`

## 9. Voltar ao ar

```bash
/opt/alt/php84/usr/bin/php artisan up
```

## 10. Testes recomendados

1. Gestão > Configurações > Identidade visual:
   - alterar logo/tamanho;
   - enviar favicon;
   - configurar rodapé.
2. Gestão > Configurações > SEO e compartilhamento:
   - título;
   - description;
   - keywords;
   - imagem padrão.
3. Editar uma campanha:
   - Instagram;
   - WhatsApp;
   - SEO próprio.
4. Abrir a campanha e testar os 3 ícones de compartilhamento.
5. Confirmar que login e Gestão usam o mesmo tamanho da logo.
6. Gestão > Configurações > Notificações por e-mail:
   - preencher SMTP;
   - salvar;
   - enviar teste.
7. Criar um pedido e confirmar um pagamento para validar notificações.
8. Visitar/clicar campanhas e conferir Analytics no Dashboard.
9. Conferir metadata server-side:

```bash
curl -s https://rifas.meu.ink/sistema/campanhas/SEU-SLUG | grep -Ei 'og:title|og:image|description|keywords|twitter:card'
```
