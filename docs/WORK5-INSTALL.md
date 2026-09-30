# WORK 5 — Identidade visual + Dashboard comercial

Projeto: APP-2026-001-RIFAS-A5  
Versão sugerida: 0.5.0

## O que entra

- Página `Configurações > Identidade visual` no Filament.
- Upload seguro de logo PNG/JPG/WebP.
- Nome do site e slogan personalizáveis.
- Cor principal dos botões personalizável.
- Cor do texto dos botões personalizável.
- A cor principal também personaliza o Filament.
- Metas por campanha:
  - meta de receita;
  - meta de números vendidos.
- Dashboard comercial com:
  - receita confirmada;
  - números vendidos;
  - pedidos aguardando pagamento;
  - campanhas ativas/meta da campanha;
  - gráfico de receita;
  - desempenho por campanha;
  - últimas vendas confirmadas;
  - filtros por período e campanha.

## Instalação

Faça backup do banco antes de executar migrations.

1. Extraia o ZIP diretamente em:

`/home/grupoaco/rifas-a5-app/`

2. Atualize o autoload:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
```

3. Confira as migrations:

```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
```

A migration `2026_09_12_030100_add_goals_to_raffles_table` deve aparecer como `Pending`.

4. Execute:

```bash
/opt/alt/php84/usr/bin/php artisan migrate --force
```

5. Garanta os defaults de branding (não altera configurações já existentes):

```bash
/opt/alt/php84/usr/bin/php artisan db:seed --class=SystemSettingSeeder --force
```

6. Atualize a versão:

```bash
sed -i 's/^APP_VERSION=.*/APP_VERSION=0.5.0/' .env
```

7. Compile o frontend:

```bash
export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH
npm run build
```

8. Preserve os arquivos públicos adaptados para sua hospedagem:

```bash
cp /home/grupoaco/rifas.meu.ink/sistema/index.php /home/grupoaco/index-work5.php
cp /home/grupoaco/rifas.meu.ink/sistema/.htaccess /home/grupoaco/htaccess-work5
```

9. Copie os assets públicos:

```bash
cp -a /home/grupoaco/rifas-a5-app/public/. /home/grupoaco/rifas.meu.ink/sistema/
```

10. Restaure `index.php` e `.htaccess`:

```bash
cp /home/grupoaco/index-work5.php /home/grupoaco/rifas.meu.ink/sistema/index.php
cp /home/grupoaco/htaccess-work5 /home/grupoaco/rifas.meu.ink/sistema/.htaccess
```

11. Limpe os caches:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

12. Valide:

```bash
/opt/alt/php84/usr/bin/php artisan a5:work5-status
```

O final esperado é:

`WORK 5 instalado corretamente.`

## Como personalizar

Acesse:

`https://rifas.meu.ink/sistema/admin`

Depois:

`Configurações > Identidade visual`

Você poderá alterar:

- Nome do site;
- Slogan;
- Logo;
- Cor principal;
- Cor do texto dos botões.

## Metas por campanha

Em `Campanhas > Editar`, ficam disponíveis:

- `Meta de receita (R$)`;
- `Meta de números vendidos`.

Essas metas alimentam o Dashboard.

## Dashboard

A página inicial do `/admin` passa a ter filtros por campanha/período e atualização automática dos principais indicadores.

## Logo

O logo é salvo em `storage/app/public/branding`, e nunca em uma pasta executável. Apenas PNG, JPG e WebP são aceitos.
