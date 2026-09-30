# WORK 5 — Changelog

## 0.5.0

### Added
- Configuração de identidade visual pelo painel.
- Upload de logo.
- Cor principal e cor do texto dos botões.
- Nome e slogan personalizáveis.
- Metas de receita e números por campanha.
- Dashboard de vendas com KPIs.
- Gráfico de receita por dia.
- Tabela de desempenho por campanha.
- Tabela de vendas confirmadas.
- Filtros do Dashboard por período e campanha.
- Comando `a5:work5-status`.

### Changed
- Frontend público passa a usar variáveis CSS de branding.
- Filament usa nome, logo e cor principal definidos no painel.
- Botões e destaques principais deixam de depender de verde fixo.

### Security
- Upload de logo limitado a PNG/JPG/WebP.
- Logo armazenado no disco público Laravel com nome gerenciado pelo framework.
- Página de identidade visual restrita a Super Admin e Administrador.
