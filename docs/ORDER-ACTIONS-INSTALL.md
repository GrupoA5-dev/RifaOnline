# Instalação — Ajuste Pedidos e Confirmação Pix

1. Envie e extraia o patch diretamente em `/home/grupoaco/rifas-a5-app/`.
2. Rode:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

Não há migration e não precisa rodar `npm run build`.

## Testes

### Pedidos
- Abra `/sistema/admin/orders`.
- Em um pedido pendente/expirado/cancelado devem aparecer **Abrir** e **Excluir**.
- Em um pedido pago, **Abrir** deve aparecer e **Excluir** não deve aparecer.
- **Abrir** deve mostrar campanha, cliente, valor, números, status do pagamento, TXID e datas.

### Pagamentos
- Abra `/sistema/admin/payments`.
- A segunda coluna deve ser **Confirmar Pix**.
- Pix estático pendente deve mostrar **Confirmar**.
- Ao clicar, o modal de confirmação deve abrir sem precisar rolar a tabela.
- Pagamento confirmado deve mostrar **Pago**.
