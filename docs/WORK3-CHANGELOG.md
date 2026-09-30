# WORK 3 — Changelog

## 0.3.0

### Added
- Pagamentos Pix via Mercado Pago usando `/v1/payments`.
- Idempotency key por pagamento.
- Webhook com validação HMAC-SHA256 (`x-signature`).
- Tabelas `payments` e `payment_events`.
- CPF/documento no cadastro de cliente.
- Conciliação automática de pagamentos pendentes.
- Painel Filament para pagamentos e eventos de webhook.
- Comando `a5:work3-status`.

### Changed
- Pedidos aprovados somente após conferir referência e valor no gateway.
- Expiração de reservas passa a reconciliar pagamentos remotos antes de liberar números.
- Marcação de pagamento considera a data real de aprovação do gateway.

### Security
- Credenciais somente por `.env`.
- Assinatura do webhook validada antes de processar evento.
- Corpo do webhook nunca é usado como prova de pagamento: o sistema consulta o recurso diretamente na API do Mercado Pago.
- Comparação HMAC em tempo constante.
