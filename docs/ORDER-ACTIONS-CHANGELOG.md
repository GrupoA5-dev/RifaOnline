# A5 Rifas — Ajuste Pedidos e Confirmação Pix

## Alterações

- Pedidos agora possuem ação **Abrir** para consultar detalhes completos em modal.
- Pedidos não financeiros possuem ação **Excluir** com confirmação.
- Pedidos pagos, reembolsados ou em chargeback ficam protegidos contra exclusão para preservar auditoria financeira.
- Ao excluir pedido não financeiro, pagamentos pendentes são removidos e números vinculados são liberados pelo cascade do banco.
- Em **Pagamentos**, a coluna **Confirmar Pix** agora é a segunda coluna da tabela, logo após o ID.
- Para Pix estático pendente, clicar em **Confirmar** abre imediatamente o modal de confirmação.
- Pagamentos já aprovados aparecem como **Pago** nessa mesma coluna.
- A antiga ação duplicada de confirmação no final da linha foi removida; **Liberar reserva** continua disponível quando aplicável.
