# APP-2026-001-RIFAS-A5 — WORK 10

Versão alvo: **0.10.0**

## Entregas

- Compartilhamento da campanha reorganizado no canto da imagem com apenas 3 ícones: copiar link, compartilhar e WhatsApp.
- Instagram e WhatsApp próprios por campanha, exibidos como ícones de contato.
- Tamanho da logo configurado em Identidade Visual aplicado também ao login e ao painel Gestão.
- Favicon configurável.
- Rodapé configurável: proprietário, ano, texto de direitos, desenvolvedor e link.
- Exclusão de clientes sempre visível:
  - sem pedidos: exclusão física;
  - com histórico: anonimização + arquivamento, preservando pedidos e pagamentos.
- SEO global configurável: título, descrição, keywords e imagem padrão de compartilhamento.
- SEO por campanha: título, descrição e keywords próprios.
- Metadata server-side para compartilhamento social com Open Graph e Twitter Card.
- `meta charset` fixado em UTF-8 por segurança e compatibilidade.
- Imagem da campanha usada automaticamente em `og:image` quando disponível.
- Configuração de notificações administrativas por e-mail via SMTP pelo painel.
- Senha SMTP criptografada com a `APP_KEY`.
- Notificação opcional de novo pedido e pagamento confirmado.
- Botão de teste de e-mail.
- Analytics de campanha sem armazenamento de IP:
  - acessos;
  - cliques nos cards;
  - clique em participar;
  - compartilhar;
  - copiar link;
  - WhatsApp;
  - Instagram.
- Dashboard com Acessos, Cliques, CTR e Cliques em participar.
- Tabela de desempenho das campanhas com acessos, cliques e CTR.
- Comando de validação: `php artisan a5:work10-status`.

## Segurança / integridade

O patch **não contém** `.env`, certificados, `public/index.php` ou `public/.htaccess`.

A publicação do frontend deve copiar apenas `public/build`, evitando sobrescrever o `index.php` especial do servidor.
