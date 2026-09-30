# Patch — Tamanho configurável da logo

Adiciona em Gestão > Configurações > Identidade visual uma barra para controlar o tamanho da logo pública.

- mínimo: 28px
- máximo: 120px
- padrão: 44px
- sem migration nova

## Instalação
1. Extraia o ZIP em `/home/grupoaco/rifas-a5-app/`.
2. Rode o seeder para cadastrar a nova configuração sem sobrescrever as existentes:
   `/opt/alt/php84/usr/bin/php artisan db:seed --class=SystemSettingSeeder --force`
3. Limpe caches:
   `/opt/alt/php84/usr/bin/php artisan optimize:clear`
   `/opt/alt/php84/usr/bin/php artisan filament:optimize-clear`
4. Compile o frontend:
   `export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH`
   `npm run build`
5. Preserve `index.php` e `.htaccess`, copie `public/` para `/home/grupoaco/rifas.meu.ink/sistema/` e restaure os dois arquivos personalizados.
