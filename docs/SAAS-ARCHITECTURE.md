# RifaOnline SaaS - Arquitetura Inicial

## Visão

Transformar o sistema atual de rifas em uma plataforma SaaS multi-organizador.

A plataforma permitirá que diferentes usuários criem e gerenciem suas próprias rifas usando a infraestrutura RifaOnline.

---

# Estrutura

RifaOnline

---

# Conceito Multi-Tenant

Cada organizador representa um ambiente isolado.

Exemplo:

Organizador:
João Silva

URL:

joao.rifaonline.org

Rifas:

joao.rifaonline.org/moto
joao.rifaonline.org/iphone

---

# Domínios

Estrutura inicial:

Site:
rifaonline.org

Painel:
app.rifaonline.org

Organizadores:

nome.rifaonline.org

Futuro:

Domínio próprio:
rifajoao.com.br

---

# Usuários e Permissões

Usuários podem possuir funções:

- Proprietário
- Administrador
- Financeiro
- Atendimento
- Vendedor

As permissões serão configuráveis.

---

# Banco de Dados

Nova entidade:

## organizers

Responsável pelo isolamento dos dados.

Campos iniciais:

- id
- user_id
- name
- slug
- phone
- email
- status

---

# Migração

Sistema atual:

Rifa Solidária

será convertido para:

Organizador:
RifaOnline Master

Nenhum dado existente será perdido.

---

# Fases

## Fase 1
Base multi-tenant.

## Fase 2
Cadastro público de organizadores.

## Fase 3
Subdomínios.

## Fase 4
Planos e cobrança.

## Fase 5
Escala SaaS.
