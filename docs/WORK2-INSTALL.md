# APP-2026-001-RIFAS-A5 — WORK 2

## Motor de Rifas

Versão alvo do projeto: `0.2.0`

### O que entra neste WORK

- Campanhas (`raffles`)
- Prêmios (`raffle_prizes`)
- Clientes (`customers`)
- Pedidos (`orders`)
- Histórico dos números do pedido (`order_tickets`)
- Alocações atuais/pagas de números (`ticket_allocations`)
- Reserva aleatória transacional
- Restrição `UNIQUE(raffle_id, number)` no banco
- Expiração de reservas sem apagar pedidos
- Ação preparada para marcar pedido como pago no WORK 3
- Painel Filament para Campanhas, Prêmios, Clientes e Pedidos
- Scheduler para expirar reservas a cada minuto
- Comando de diagnóstico `a5:work2-status`

## Segurança / integridade

A tabela `ticket_allocations` é a fonte de verdade dos números atualmente ocupados.
O índice único de `raffle_id + number` impede que o mesmo número seja atribuído duas vezes na mesma campanha.

Quando uma reserva expira:

1. o pedido muda para `expired`;
2. `order_tickets` preserva os números no histórico como `released`;
3. as linhas reservadas em `ticket_allocations` são removidas, liberando os números para nova compra.

Pedidos não são apagados.

---

# INSTALAÇÃO NO SERVIDOR ATUAL

Aplicação privada:

`/home/grupoaco/rifas-a5-app`

Antes da migração, faça um backup do banco `grupoaco_rifas` pelo cPanel/phpMyAdmin.

## 1. Extrair o patch

Envie o ZIP do WORK 2 para `/home/grupoaco/rifas-a5-app` e extraia **dentro dessa pasta**, de modo que o conteúdo `app/`, `database/`, `config/` e `routes/` seja mesclado com o projeto atual.

O patch não contém `.env`, `public/index.php` nem `.htaccess`.

## 2. Atualizar autoload

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
```

## 3. Conferir migrations antes de executar

```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
```

As novas migrations do WORK 2 devem aparecer como `Pending`.

## 4. Executar migrations

```bash
/opt/alt/php84/usr/bin/php artisan migrate --force
```

Nunca use `migrate:fresh` em produção.

## 5. Atualizar versão do projeto

Edite o `.env`:

```env
APP_VERSION=0.2.0
```

Ou pelo terminal:

```bash
sed -i 's/^APP_VERSION=.*/APP_VERSION=0.2.0/' .env
```

## 6. Limpar caches do Laravel/Filament

```bash
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

## 7. Verificar instalação

```bash
/opt/alt/php84/usr/bin/php artisan a5:work2-status
```

Resultado esperado:

`WORK 2 instalado corretamente.`

## 8. Verificar scheduler

```bash
/opt/alt/php84/usr/bin/php artisan schedule:list
```

Deve existir:

`raffles:expire-reservations`

Para o servidor executar o Scheduler automaticamente, o cron do cPanel deve chamar a cada minuto:

```bash
/opt/alt/php84/usr/bin/php /home/grupoaco/rifas-a5-app/artisan schedule:run >> /dev/null 2>&1
```

## 9. Teste no painel

Acesse:

`https://rifas.meu.ink/sistema/admin`

O menu deverá apresentar:

- Campanhas
- Prêmios
- Clientes
- Pedidos

Crie primeiro uma campanha em **Campanhas**.

### Exemplo inicial

- Título: Campanha Teste
- Slug: campanha-teste
- Status: Rascunho
- Distribuição: Números aleatórios
- Preço: 1,00
- Quantidade total: 10000
- Dígitos: 4
- Compra mínima: 1
- Compra máxima: 100
- Reserva: 15 minutos

Mantenha como `Rascunho` até o checkout do WORK 3/4 estar conectado.

---

# REVERSÃO

Se a migração falhar durante a instalação, pare e consulte o erro antes de executar qualquer rollback.

Como essas tabelas são novas, durante homologação é possível reverter apenas o último lote com:

```bash
/opt/alt/php84/usr/bin/php artisan migrate:rollback --step=6 --force
```

**Não execute esse comando se já houver pedidos reais.**

---

# PRÓXIMO WORK

## WORK 3 — PIX / Mercado Pago

- interface `PaymentGateway`;
- Mercado Pago PIX;
- criação idempotente de pagamento;
- QR Code e copia-e-cola;
- webhook autenticado;
- conciliação;
- baixa do pedido;
- logs de webhook;
- tratamento de expiração, reembolso e eventos duplicados.
