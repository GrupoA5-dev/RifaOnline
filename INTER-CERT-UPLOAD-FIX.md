# A5 Rifas — correção de upload dos certificados Banco Inter

Corrige o falso erro `validation.mimetypes` no upload de `.crt` e `.key`.

## O que muda
- Não confia mais no MIME informado pelo navegador para CRT/PEM/KEY/PFX.
- Continua armazenando os arquivos no disco privado `local`, em `storage/app/private/secure/inter` (ou caminho equivalente do disco local configurado).
- Valida o conteúdo real com OpenSSL ao salvar.
- Verifica se o certificado X.509 é válido.
- Verifica se a chave privada é válida.
- Verifica se CRT e KEY pertencem ao mesmo par criptográfico.
- Também valida PFX/P12 e senha quando esse modo for usado.

## Instalação
Extraia o patch em `/home/grupoaco/rifas-a5-app/`, sobrescrevendo o arquivo existente.

Depois:

```bash
cd /home/grupoaco/rifas-a5-app
/opt/alt/php84/usr/bin/php -l app/Filament/Pages/InterPixSettingsPage.php
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan filament:optimize-clear
```

Não requer migration, Composer ou npm build.
