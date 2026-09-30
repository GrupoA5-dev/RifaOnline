# Correção — Campanha reativada não aparecia

## Causa
A home exigia simultaneamente `status = active`, `starts_at <= now()` e `ends_at > now()`.
Ao reativar uma campanha encerrada, o campo `ends_at` antigo permanecia no passado. Assim, a campanha tinha status Ativa no painel, mas era removida da home e também marcada como não comprável.

## Correção
- `status = active` passa a ser a fonte de verdade para campanhas ativas.
- Campanhas com status `scheduled` continuam fora da home.
- Datas antigas de início/fim não escondem uma campanha que o administrador reativou explicitamente.
- Não altera pedidos, números, pagamentos ou banco de dados.
- Não exige migration nem rebuild do frontend.
