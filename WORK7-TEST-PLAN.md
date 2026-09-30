# WORK 7 — Plano de homologação

## Teste A — OAuth sem cobrança

`php artisan inter:pix-test`

Esperado: OAuth OK, token Bearer e validade retornada.

## Teste B — Webhook

`php artisan inter:webhook-register`
`php artisan inter:webhook-status`

Esperado: a URL cadastrada corresponde ao domínio público da aplicação.

## Teste C — Cobrança R$ 1,00

Criar compra real de 1 número em uma campanha de R$ 1,00.

Esperado:
- gateway `inter_pix`;
- `txid` exclusivo;
- Pix Copia e Cola retornado;
- QR Code exibido;
- prazo aproximadamente 5 minutos;
- tela informa confirmação automática.

## Teste D — Pagamento dentro do prazo

Pagar o Pix a partir de outra instituição.

Esperado:
- webhook chega ou a conciliação de contingência identifica o pagamento;
- `payments.status = approved`;
- `orders.status = paid`;
- tickets e allocations mudam para `paid`;
- números não ficam disponíveis para outra compra.

## Teste E — Não pagar

Gerar nova reserva e deixar expirar.

Esperado:
- durante a margem de segurança o número continua reservado;
- o sistema faz conferência final no Banco Inter;
- sem pagamento, pedido passa para expirado e o número é liberado.

## Teste F — Webhook falso

Um POST externo com um `txid` conhecido não deve ser suficiente para marcar o pedido como pago. O backend consulta o Banco Inter antes da confirmação.
