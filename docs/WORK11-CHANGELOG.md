# WORK 11 — Security & Production Finalization

## Adicionado
- 2FA obrigatório no painel Filament por aplicativo autenticador (TOTP).
- 10 códigos de recuperação por administrador.
- Perfil do administrador habilitado para gerenciamento do 2FA.
- Código de acesso de 6 dígitos para cada cliente.
- Consulta “Meus números” agora exige WhatsApp + código de acesso.
- Rate limit adicional por IP + WhatsApp na consulta de números.
- Ação administrativa para regenerar o código do cliente.
- Senhas administrativas com mínimo de 12 caracteres, maiúscula, minúscula, número e símbolo.
- Headers de segurança reforçados (HSTS, CSP parcial segura, no-store em páginas sensíveis).
- Sanitização de auditoria para nunca gravar senha, token, segredo, PIN, private key ou recovery codes.
- Tela de Auditoria somente para SuperAdmin.
- Comando `a5:security-status` para checagem final.

## Compatibilidade
- Feito para a base Laravel 13 / Filament 5.8.1 do APP-2026-001-RIFAS-A5.
- Mantém Pix automático Banco Inter, campanhas, WhatsApp como chave do cliente e rotas atuais.
- Não altera `public/index.php` nem `.htaccess`.
