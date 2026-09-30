# A5 Rifas — Patch Pix Estático

Este patch substitui o Mercado Pago por Pix estático BR Code, sem API externa.

## 1. Configuração no `.env`

Adicione em `/home/grupoaco/rifas-a5-app/.env`:

```env
PIX_MODE=static
PIX_KEY="SUA_CHAVE_PIX"
PIX_MERCHANT_NAME="NOME DO RECEBEDOR"
PIX_MERCHANT_CITY="SUA CIDADE"
PIX_TXID_PREFIX=A5
```

Regras práticas:
- `PIX_MERCHANT_NAME`: até 25 caracteres após remoção de acentos.
- `PIX_MERCHANT_CITY`: até 15 caracteres após remoção de acentos.
- A chave pode ser CPF/CNPJ, e-mail, telefone ou chave aleatória cadastrada no DICT.

## 2. Instalação

Extraia o ZIP diretamente em:

```text
/home/grupoaco/rifas-a5-app/
```

Depois:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php /opt/cpanel/ea-wappspector/composer.phar dump-autoload --optimize
export PATH=/opt/alt/alt-nodejs22/root/usr/bin:$PATH
npm install
npm run build
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
/opt/alt/php84/usr/bin/php artisan a5:pix-static-status
```

## 3. Publicar os assets

Preserve os arquivos públicos customizados:

```bash
cp /home/grupoaco/rifas.meu.ink/sistema/index.php /home/grupoaco/index-pix-static.php
cp /home/grupoaco/rifas.meu.ink/sistema/.htaccess /home/grupoaco/htaccess-pix-static
cp -a /home/grupoaco/rifas-a5-app/public/. /home/grupoaco/rifas.meu.ink/sistema/
cp /home/grupoaco/index-pix-static.php /home/grupoaco/rifas.meu.ink/sistema/index.php
cp /home/grupoaco/htaccess-pix-static /home/grupoaco/rifas.meu.ink/sistema/.htaccess
```

## 4. Como confirmar pagamento

No painel:

```text
Admin > Pagamentos
```

Um Pix estático pendente terá a ação **Confirmar Pix**.

1. Confira o crédito na conta bancária.
2. Confira o valor e, quando disponível no extrato, o TXID.
3. Informe a data/hora real do recebimento.
4. Confirme.

O sistema marca, de forma transacional:
- pagamento = aprovado;
- pedido = pago;
- números = pagos.

## 5. Como liberar uma reserva vencida

Pix estático não possui confirmação automática. Por segurança, pedidos que já geraram QR Code não são liberados automaticamente quando o contador vence.

Depois do prazo, o painel exibirá **Liberar reserva**.

Use apenas após conferir que nenhum Pix foi recebido. O pedido será expirado e os números voltarão a ficar disponíveis.

## Importante

O QR Code estático não possui vencimento bancário real. O frontend para de exibi-lo após o prazo, porém um código já copiado pode continuar tecnicamente pagável. Essa limitação só desaparece quando integrarmos uma API Pix dinâmica do banco/PSP.
