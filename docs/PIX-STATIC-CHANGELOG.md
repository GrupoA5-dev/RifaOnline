# Patch Pix Estático

## Added
- Gerador BR Code Pix estático seguindo o padrão BCB.
- Valor do pedido incluído no payload.
- TXID alfanumérico exclusivo de até 25 caracteres por pedido.
- QR Code gerado localmente no navegador com `qrcode`.
- Ação administrativa **Confirmar Pix** com data/hora real do extrato.
- Ação **Liberar reserva** para pedidos vencidos após conferência bancária.
- Comando `a5:pix-static-status`.

## Changed
- Checkout deixa de chamar Mercado Pago.
- Webhook Mercado Pago deixa de ser registrado.
- Reconciliação automática deixa de ser executada pelo Scheduler.
- Pix estático pendente NÃO é liberado automaticamente no vencimento, evitando revenda de número caso o cliente tenha pago e o administrador ainda não tenha conferido.
- A tela deixa de exibir o QR Code após o contador zerar.

## Security
- Pagamento só vira `paid` por confirmação administrativa explícita.
- O mesmo fluxo transacional consolida pedido e números.
- Liberação de números exige confirmação administrativa depois do prazo.
- CSRF volta a proteger todas as rotas web, pois não há webhook externo ativo.
