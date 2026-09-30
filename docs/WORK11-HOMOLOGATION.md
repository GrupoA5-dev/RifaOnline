# Homologação final — v1.0.0

1. Home abre sem erro.
2. Campanha ativa abre e permite reserva.
3. Pix Banco Inter é criado com TXID `A5RIFA...`.
4. Pagamento é reconhecido automaticamente.
5. Mensagem “PAGAMENTO CONFIRMADO COM SUCESSO” aparece e redireciona em 5 segundos.
6. “Meus números” rejeita WhatsApp sem PIN correto.
7. WhatsApp + PIN correto abre os números.
8. Após regenerar PIN no admin, o código antigo deixa de funcionar.
9. Login admin exige 2FA após senha.
10. Código TOTP válido permite entrar; código inválido não permite.
11. Códigos de recuperação ficam guardados fora do servidor/painel.
12. Auditoria é visível apenas para SuperAdmin.
13. `a5:security-status` retorna todos os itens como OK.
14. `a5:preflight` retorna aprovado.
15. Cron/scheduler está ativo a cada minuto.
16. Backup do banco foi testado e existe cópia fora da hospedagem.

Quando os itens acima passarem, marcar release como `v1.0.0 - Produção`.
