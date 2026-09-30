# WORK 4 — Frontend público conectado ao motor real

## O que entra
- Home dinâmica com campanhas reais.
- Página pública de campanha.
- Seleção de quantidade.
- Checkout com nome, e-mail, WhatsApp e CPF.
- Reserva transacional usando o motor do WORK 2.
- Geração de Pix usando o WORK 3.
- QR Code e Pix copia e cola.
- Polling de status de pagamento.
- Confirmação automática e exibição dos números.
- Ranking baseado apenas em pedidos pagos.
- Validação de CPF.

## Instalação
1. Backup dos arquivos atuais e do banco.
2. Extrair este patch diretamente em `/home/grupoaco/rifas-a5-app/`.
3. Rodar `composer dump-autoload --optimize`.
4. Atualizar `APP_VERSION=0.4.0`.
5. Rodar `npm install` (se ainda não houver node_modules) e `npm run build`.
6. Copiar os assets públicos compilados para `/home/grupoaco/rifas.meu.ink/sistema/`, preservando o `index.php` e `.htaccess` personalizados.
7. Limpar caches.
8. Rodar `php artisan a5:work4-status`.

Não há migrations novas no WORK 4.
