# Sistema Visa — Etapa 2 — Parte 2.10.3 — Bootstrap e Runtime Visual

## Objetivo

Migrar a leitura e aplicação de tema/cor para o escopo individual do usuário autenticado, preservando:

- configuração institucional global
- estabilidade do first paint
- compatibilidade de consumidores visuais relevantes

## Escopo executado

- bootstrap técnico mantido com cache visual combinado
- runtime visual migrado para `BaseStore.userPrefs`
- fallback legado preservado temporariamente para evitar regressão
- preview de relatórios ajustado para preferências visuais do usuário

## Implementação realizada

### 1. Runtime visual

O arquivo de personalização passou a compor o estado visual a partir de dois escopos:

- `BaseStore.prefs` para conteúdo global institucional
- `BaseStore.userPrefs` para tema, cor e preferências visuais individuais

Regra aplicada:

- tema e preview visual usam o escopo individual quando disponível
- em ausência de dados individuais, o sistema usa fallback legado temporário

### 2. Toggle de tema na área privada

O toggle de tema foi migrado para gravar em `BaseStore.userPrefs`.

Com isso:

- a troca de tema deixa de escrever no escopo global
- o cache técnico de bootstrap continua sendo sincronizado com o estado combinado

### 3. Preview do Financeiro

O preview de relatórios passou a consultar preferências visuais individuais antes do fallback global legado.

### 4. Hidratação do usuário autenticado na UI

Foi corrigida a hidratação do usuário na `BaseStore` para priorizar o usuário real da sessão autenticada via endpoint `me`.

Com isso:

- cada navegador passa a refletir o usuário realmente logado
- a chave `user_ui_prefs:<user_id>` deixa de depender de usuário global compartilhado
- a validação multiusuário fica coerente no sidebar e nas preferências visuais

### 5. Separação do payload do painel de personalização

O modal de Ferramentas passou a salvar em contratos distintos:

- bloco global institucional em `BaseStore.prefs`
- bloco visual individual em `BaseStore.userPrefs`

Com isso:

- tema e cor deixam de vazar para o escopo global
- identidade e branding institucional permanecem compartilhados

## Contrato preservado

### Global institucional

Permanece em `BaseStore.prefs`:

- identidade
- branding institucional
- dados corporativos globais

### Visual individual

Passa a ser lido em `BaseStore.userPrefs`:

- tema
- cor de acento
- preferências visuais de interface

## Compatibilidade transitória

Durante esta parte, foi mantido fallback para o contrato antigo em pontos de leitura visual.

Objetivo:

- evitar regressão durante a migração controlada

O fechamento dessa transição fica reservado para:

- Parte `2.10.4`
- Parte `2.10.5`

## Resultado esperado da Parte 2.10.3

Ao final desta parte:

- tema/cor passam a respeitar o usuário autenticado
- bootstrap continua estável
- o escopo global institucional permanece preservado
- consumidores visuais críticos passam a operar com o novo contrato

## Apoio de desenvolvimento

Para viabilizar testes reais de comportamento multiusuário, foi criado um segundo usuário temporário de desenvolvimento.

Dados cadastrados:

- nome: `Darlan`
- e-mail: `darlan@visaremocoes.com.br`
- senha: `123456`
- papel: `admin`

Regra de uso:

- usuário mantido apenas para testes durante o desenvolvimento
- remoção prevista quando o módulo real de usuários estiver implementado
