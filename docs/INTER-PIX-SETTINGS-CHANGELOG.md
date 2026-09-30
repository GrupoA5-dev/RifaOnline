# Inter Pix Settings — 0.4.3

## Added
- Página administrativa `Pix Banco Inter`.
- Ambiente Sandbox/Produção.
- Campos para Client ID, Client Secret, chave Pix e conta corrente.
- Upload privado de PFX/P12 ou CRT/PEM + KEY/PEM.
- Criptografia de Client Secret e senha PFX.
- URL de webhook calculada automaticamente.
- Comando `a5:inter-pix-settings-status`.
- Restrição da página ao Super Admin.
- Registro de auditoria ao salvar configurações.

## Security
- Certificados não são armazenados em diretório público.
- Segredos não são mostrados novamente após o salvamento.
- Auditoria registra apenas status de configuração, nunca os valores secretos.
