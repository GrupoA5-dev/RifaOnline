# A5 Rifas — Configuração administrativa Pix Banco Inter

Este patch NÃO ativa chamadas reais ao Banco Inter ainda. Ele cria a tela segura no painel para que as credenciais sejam preenchidas quando estiverem disponíveis.

## O que é configurável no painel

- Ativar/desativar Pix automático Banco Inter
- Sandbox / Produção
- Client ID
- Client Secret (criptografado)
- Chave Pix
- Conta corrente opcional
- Formato do certificado: PFX/P12 ou CRT/PEM + KEY/PEM
- Upload privado dos certificados
- Senha do PFX (criptografada)
- URL do webhook exibida automaticamente

## Segurança

- A página é acessível somente ao `super_admin`.
- `client_secret` e senha do PFX são criptografados com a `APP_KEY` do Laravel.
- Certificados são gravados no disk `local`, dentro de `storage/app/secure/inter`.
- Nada é copiado para `public/` ou `/sistema/`.
- Segredos existentes nunca são exibidos novamente no formulário.

## Instalação

Extraia o ZIP em:

`/home/grupoaco/rifas-a5-app/`

Depois execute:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

Não há migration nova e não há build de frontend Vue neste patch.

Acesse:

`https://rifas.meu.ink/sistema/admin`

No menu aparecerá **Configurações > Pix Banco Inter** para o Super Admin.

## Status via terminal

```bash
/opt/alt/php84/usr/bin/php artisan a5:inter-pix-settings-status
```

Enquanto você ainda não possuir certificado/credenciais, o comando mostrará `PENDING`, o que é normal.

## Próxima etapa

Quando a configuração estiver disponível, o próximo patch usará estes mesmos dados para:

- OAuth do Inter com cache de token
- mTLS/certificado
- criação da cobrança imediata com `txid`
- expiração alinhada à reserva da campanha
- Pix Copia e Cola / QR Code
- webhook do Inter
- consulta de confirmação
- baixa automática do pedido e dos números
