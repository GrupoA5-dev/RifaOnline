# Correção — Banco Inter x-conta-corrente

Remove o envio automático do header `x-conta-corrente` nas chamadas de saída da API Pix.
O Inter documenta `x-conta-corrente` nos callbacks para identificar a conta relacionada ao evento.

## Instalação
1. Extraia na raiz de `/home/grupoaco/rifas-a5-app`.
2. Rode lint no arquivo.
3. Rode `php artisan optimize:clear`.
4. Rode novamente `php artisan inter:webhook-register`.

Sem migration e sem build do frontend.
