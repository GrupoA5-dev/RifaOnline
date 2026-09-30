# A5 Rifas — WORK 1

Fundação do novo sistema de rifas `APP-2026-001-RIFAS-A5`.

## Stack
- PHP 8.4
- Laravel 13
- Filament 5
- Vue 3
- Inertia 3
- Tailwind CSS 4
- MySQL/MariaDB

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure o banco no `.env` e defina `ADMIN_EMAIL` e `ADMIN_PASSWORD` com uma senha forte (12+ caracteres), então:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
```

Para desenvolvimento:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

## Produção / staging
- document root deve apontar para `/public`;
- `APP_DEBUG=false`;
- `APP_FORCE_HTTPS=true` quando o proxy/webserver estiver configurado corretamente;
- `SESSION_SECURE_COOKIE=true` sob HTTPS;
- execute `php artisan optimize` após deploy;
- configure worker para `php artisan queue:work`;
- configure cron a cada minuto: `php artisan schedule:run`.

## URLs
- `/` — frontend inicial
- `/admin` — Filament
- `/health` — health check resumido
- `/up` — health check nativo do Laravel

## Segurança
O usuário só acessa o painel se estiver ativo e possuir perfil `super_admin` ou `admin`.
A auditoria não salva senha, remember token, access token ou secret.

## Próximo passo
WORK 2: campanhas, clientes, pedidos, bilhetes e reserva concorrente com `UNIQUE(raffle_id, number)`.
