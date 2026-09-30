# WORK 4 — 0.4.0

## Added
- Frontend público Vue/Inertia conectado ao banco.
- Home dinâmica de campanhas.
- Página de campanha inspirada no Savawards.
- Checkout público protegido por CSRF e rate limit.
- CPF validado no servidor.
- Reserva de bilhetes integrada ao motor transacional.
- Página Pix com QR Code, copia e cola e polling automático.
- Confirmação visual de pagamento e exibição dos números.
- Ranking usando pedidos pagos.
- Comando `a5:work4-status`.

## Security
- Não há consulta pública apenas por telefone/e-mail.
- A página do pedido usa UUID + token aleatório de 64 caracteres.
- Valores e disponibilidade são recalculados no servidor.
- O frontend não define o preço nem os números reservados.
