# Sistema Visa — Etapa 2 — Parte 2.10.1 — Mapeamento de Escopo e Contrato

## Objetivo

Definir formalmente o escopo de persistência da Correção 2.10, separando:

- configuração global do sistema
- preferências visuais individuais por usuário

Sem executar ainda a mudança operacional de persistência.

## Contexto

Durante a estabilização da Parte 2.9, foi confirmado que tema e cor de acento estavam sendo tratados de forma global.

Esse comportamento é inadequado para ambiente multiusuário porque mistura:

- identidade institucional compartilhada
- preferência visual pessoal

A Correção 2.10 foi aberta para separar esses dois contratos mantendo a arquitetura atual:

`UI -> Store -> SysStore -> API -> MySQL`

## Decisão de escopo

### 1. Configuração global do sistema

Permanece compartilhada entre todos os usuários.

Campos classificados como globais:

- nome do sistema
- nome da empresa
- identidade institucional
- logo
- favicon
- textos institucionais
- dados corporativos refletidos em cabeçalhos, rodapés e relatórios

### 2. Preferências visuais do usuário

Passam a ser persistidas por usuário autenticado.

Campos classificados como individuais:

- tema `light` / `dark`
- cor de acento
- preferências visuais de interface
- preferências futuras de UI que não alterem a identidade institucional

## Contrato funcional aprovado

### Contrato global

O contrato global permanece como fonte de verdade para identidade institucional.

Escopo:

- compartilhado
- carregado para todos os usuários
- sem influência de preferência pessoal

### Contrato individual

O contrato individual passa a depender do usuário autenticado.

Regra:

- leitura e gravação sempre vinculadas ao `auth_user.id`
- sem impactar a experiência visual de outros usuários

## Chave conceitual aprovada

Para as próximas partes da Correção 2.10, a persistência individual deverá usar chave por usuário.

Referência conceitual aprovada:

- `user_ui_prefs:<user_id>`

Observação:

- a chave exata poderá ser adaptada na implementação desde que preserve o escopo por usuário e a legibilidade do contrato

## Mapeamento do estado atual

### Estado atual da personalização visual

Hoje, tema e cor de acento ainda estão acoplados ao armazenamento global em `tools_sys_prefs_v2`.

Consequência:

- um usuário altera a experiência visual de todos os demais

### Estado alvo após a Correção 2.10

Após a implementação completa:

- `tools_sys_prefs_v2` ficará restrita ao escopo global
- preferências visuais sairão do escopo global
- preferências visuais serão lidas e gravadas por usuário autenticado

## Regras obrigatórias para as próximas partes

- não quebrar o bootstrap visual de first paint
- não introduzir persistência híbrida sem contrato explícito
- manter compatibilidade com a arquitetura de stores existente
- não mover identidade institucional para escopo individual
- não manter tema/cor em escopo global após a conclusão da Correção 2.10

## Resultado esperado da Parte 2.10.1

Ao final desta parte:

- o contrato de escopo fica formalmente definido
- a separação entre global e individual fica validada
- a equipe pode avançar para a Parte 2.10.2 sem ambiguidade de modelagem
