# WORK 13 / Etapa 0 — cPanel, SSH e homologação (sem deploy)

**Estado:** guia de preparação. Nenhum passo desta etapa autoriza tocar no sistema antigo `rifas.meu.ink`.

## Topologia

- Legado: `/home/grupoaco/rifas.meu.ink` — NUNCA alterar.
- Desenvolvimento existente: `/home/grupoaco/rifas-a5-app` — não usar como ambiente de homologação.
- Homologação proposta: `/home/grupoaco/rifaonline-homolog` (criar somente após confirmação do cPanel).
- URL proposta: `https://homolog.rifaonline.org` (DNS, document root e SSL ainda pendentes).
- Banco, usuário SQL, `.env`, `APP_KEY`, sessões, cache, filas e storage exclusivos para homologação.

## 1. Diagnóstico local — somente leitura

Abrir cPanel > Advanced > Terminal e executar:

```sh
printf 'USUARIO=%s\nHOME=%s\nHOST=%s\n' "$(id -un)" "$HOME" "$(hostname -f 2>/dev/null || hostname)"
printf 'PHP='; command -v php || true
printf 'GIT='; command -v git || true
printf 'SSH='; command -v ssh || true
for d in /home/grupoaco/rifas-a5-app /home/grupoaco/rifas.meu.ink /home/grupoaco/rifaonline-homolog; do
  if [ -d "$d" ]; then echo "EXISTE: $d"; else echo "NAO EXISTE: $d"; fi
done
```

**Compartilhar apenas esse resumo**, nunca `.env`, senha, chave privada, token ou conteúdo do banco. Conferir também com a hospedagem se o SSH de entrada está disponível externamente e em qual porta. Ter Terminal no cPanel não garante que o GitHub Actions consiga entrar por SSH.

## 2. Preparar chave exclusiva do diagnóstico (quando SSH externo estiver confirmado)

Criar uma chave **somente para o diagnóstico**, não reutilizar chave de FTP, repositório, nem a chave de produção. Gerar no computador administrativo com `ssh-keygen -t ed25519` sem colocar a chave privada no chat. No cPanel > Security > SSH Access > Manage SSH Keys, importar **somente a chave pública** e autorizar. Reservar a privada para um segredo de ambiente do GitHub.

Em `$HOME/bin/rifaonline-staging-probe`, instalar o script abaixo no Terminal. O script apenas confere existência de diretório, presença do `artisan` e versão de PHP:

```sh
umask 077
mkdir -p "$HOME/bin"
cat > "$HOME/bin/rifaonline-staging-probe" <<'SH'
#!/bin/sh
set -eu
target=/home/grupoaco/rifaonline-homolog
printf 'RIFAONLINE_STAGING_PROBE_OK\n'
if [ -d "$target" ]; then
  printf 'HOMOLOG_DIR=EXISTE\n'
else
  printf 'HOMOLOG_DIR=AUSENTE\n'
fi
if [ -f "$target/artisan" ]; then
  printf 'LARAVEL=EXISTE\n'
else
  printf 'LARAVEL=AUSENTE\n'
fi
if command -v php >/dev/null 2>&1; then
  php -v | head -n 1
else
  printf 'PHP=NAO_ENCONTRADO_NO_PATH\n'
fi
SH
chmod 700 "$HOME/bin/rifaonline-staging-probe"
```

**ESSENCIAL:** no arquivo `~/.ssh/authorized_keys`, ajustar apenas a linha **dessa chave pública** para iniciar com:

```text
command="/home/grupoaco/bin/rifaonline-staging-probe",restrict ssh-ed25519 AAAA... comentario-da-chave
```

O trecho `AAAA...` é um marcador: manter a chave pública **real e completa**. **Não sobrescrever as demais linhas** de `authorized_keys`. A diretiva `command=` limita esta chave a executar o script acima; `restrict` desativa encaminhamentos e funções interativas. Não habilitar a automação com acesso SSH amplo. Conferir com o provedor eventuais limitações do jailed shell.

## 3. GitHub Environment

No repositório `GrupoA5-dev/RifaOnline`: Settings > Environments > New environment > `homologacao`.

Recomendações: revisão humana obrigatória se disponível; restrições de branches; impedir deploy direto de `main`; manter secretos só no environment.

**Environment variables**:
- `STAGING_SSH_HOST` = hostname público validado pela hospedagem
- `STAGING_SSH_USER` = retorno de `id -un` no cPanel
- `STAGING_SSH_PORT` = porta SSH informada pela hospedagem

**Environment secrets**:
- `STAGING_SSH_PRIVATE_KEY` = conteúdo da chave privada exclusiva para o diagnóstico
- `STAGING_SSH_KNOWN_HOSTS` = entrada `known_hosts` do servidor, **após validação da fingerprint com o provedor**. Não confiar cegamente em `ssh-keyscan`.

Nunca colar secrets em issues, commits, PRs ou mensagens.

## 4. Ações / execução manual

O workflow `.github/workflows/work13-ssh-diagnostic.yml` só executa em `workflow_dispatch` e apenas chama a chave SSH restrita. Intencionalmente manda `false` ao servidor; sem `command=` no `authorized_keys`, o teste falha. A saída esperada contém a linha exata `RIFAONLINE_STAGING_PROBE_OK`.

**Limitação do GitHub:** `workflow_dispatch` só é reconhecido quando a definição do workflow existe na branch padrão `main`. Esse workflow foi preparado primeiro em `feature/work13-automation`, via PR rascunho. Antes de usá-lo, revisar e incorporar uma versão aprovada do workflow à branch padrão **sem mesclar as funcionalidades SaaS ainda incompletas**.

## 5. Ambiente web e SQL independente (etapa posterior)

- Criar o subdomínio com document root `/home/grupoaco/rifaonline-homolog/public` (não a pasta raiz da aplicação); configurar DNS/SSL.
- Criar banco **novo e vazio** e usuário SQL **exclusivo** no cPanel > MySQL Databases.
- Criar `.env` somente na homologação, com `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://homolog.rifaonline.org`, `APP_KEY` distinto, `DB_*` apontando somente ao novo banco; usar cache/sessões/filas isolados.
- Desabilitar pagamentos reais/credenciais e-mail externas na homologação até haver sandbox, webhook de testes e revisão.
- Não copiar automaticamente `.env`, chaves PIX, arquivos de clientes ou banco do legado.
- Nunca executar `migrate --force`, `db:wipe`, `migrate:fresh`, `seed` ou comandos destrutivos sem checar o ambiente e ter backup restaurável.
- O workflow desta etapa **NÃO** faz deploy, `git pull`, build, migrations ou gravações remotas.

## 6. Riscos / ressalvas

Conta compartilhada de cPanel geralmente tem acesso a vários diretórios sob o mesmo usuário Unix; bloquear o caminho no workflow **não basta**, por isso a chave do diagnóstico requer comando forçado. Uma chave de deploy futura deve ter escopo distinto e aprovação explícita. Se o provedor bloquear SSH externo, escolher método alternativo de implantação **com segurança**, sem relaxar controles.
