# WORK 6 — Clientes, Ganhadores, Compartilhamento e Meus Números

## Added
- Edição de clientes no painel.
- Exclusão de clientes sem pedidos.
- Acesso público seguro "Meus números" por token aleatório de 64 caracteres.
- Token de acesso salvo no navegador após a compra.
- Página de consulta de pedidos e números do participante.
- Cadastro e gestão de ganhadores.
- Ação "Ganhador" diretamente na tabela de campanhas.
- Exibição pública de ganhadores na página da campanha.
- Botões de compartilhamento: WhatsApp, Facebook, Telegram, compartilhamento nativo e copiar link.
- Comando `a5:work6-status`.

## Changed
- Botão público "Admin" renomeado para "Gestão".
- Checkout agora exibe link e código de acesso para "Meus números".

## Security
- Consulta "Meus números" não usa telefone/CPF na URL; usa token aleatório de alta entropia.
- Ganhador só pode ser registrado para número pertencente a pedido pago da mesma campanha.
- Cliente com histórico de pedidos não pode ser excluído, preservando integridade financeira.
