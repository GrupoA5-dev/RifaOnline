# Changelog

## 0.2.0 — WORK 2

### Added
- Motor de campanhas e prêmios.
- Clientes e pedidos.
- Reserva segura de números.
- Histórico de números por pedido.
- Expiração de reservas sem exclusão do pedido.
- Recursos Filament de campanhas, prêmios, clientes e pedidos.
- Comando `raffles:expire-reservations`.
- Comando `a5:work2-status`.

### Security / Integrity
- Restrição única `raffle_id + number` na tabela de alocações.
- Reserva em transação de banco.
- Valores monetários internos armazenados em centavos.
- Pedido preservado após expiração.
