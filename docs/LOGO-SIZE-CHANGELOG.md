# Changelog

## 0.6.1
### Added
- Controle deslizante de 28px a 120px para o tamanho da logo no cabeçalho público.
- Persistência em `system_settings` como `branding.logo_size`.
- Compartilhamento do tamanho via Inertia para o frontend.

### Security
- Valor limitado no backend entre 28 e 120 px, mesmo se a requisição for manipulada.
