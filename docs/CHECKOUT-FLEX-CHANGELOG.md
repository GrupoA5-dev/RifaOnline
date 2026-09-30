# A5 Rifas — Patch 0.4.2

## Added
- Campos do checkout configuráveis por campanha:
  - nome completo;
  - e-mail;
  - WhatsApp/telefone;
  - CPF.
- Escolha manual real de números.
- Endpoint paginado de disponibilidade de números.
- Reserva transacional de números selecionados.
- Lista em blocos de 100 para não renderizar milhares/milhões de botões de uma vez.
- Números ocupados aparecem bloqueados.
- Seleção permanece ao navegar entre páginas.
- Validação final no servidor antes da reserva.
- Comando `a5:checkout-flex-status`.

## Changed
- `customers.phone` passa a aceitar NULL quando a campanha não solicita telefone.
- Checkout público mostra somente os campos ativados naquela campanha.
- Campanhas em modo manual usam `ReserveSelectedTickets`; modo aleatório continua usando `ReserveRandomTickets`.

## Security / Integrity
- O navegador não é fonte de verdade para disponibilidade.
- `UNIQUE(raffle_id, number)` continua protegendo contra venda duplicada.
- Números são novamente verificados dentro de transação no momento da reserva.
- Caso um número seja reservado por outra pessoa entre a visualização e o envio, a operação inteira é cancelada e o cliente recebe uma mensagem para atualizar a seleção.
