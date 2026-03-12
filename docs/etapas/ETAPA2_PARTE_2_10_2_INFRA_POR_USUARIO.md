# Sistema Visa — Etapa 2 — Parte 2.10.2 — Infra de Persistência por Usuário

## Objetivo

Implementar a infraestrutura de persistência para preferências visuais por usuário autenticado, sem migrar ainda o bootstrap visual nem o runtime de aplicação de tema/cor.

## Escopo executado

- manutenção de `tools_sys_prefs_v2` como chave global
- criação da chave individual conceitual por usuário
- preparação da `BaseStore` para lidar com preferências individuais
- preservação de compatibilidade com consumidores atuais

## Contrato implementado

### Escopo global preservado

Permanece em:

- `tools_sys_prefs_v2`

Uso:

- configuração institucional compartilhada

### Escopo individual preparado

Passa a existir como contrato de infraestrutura:

- `user_ui_prefs:<user_id>`

Uso:

- tema
- cor de acento
- preferências visuais individuais

## Implementação realizada

### 1. SysStore

Foi adicionada a capacidade de resolver a chave individual por usuário e operar esse namespace sem alterar a API global existente.

Infra adicionada:

- `userUiPrefsKey(userId)`
- `getUserUiPrefs(userId)`
- `setUserUiPrefs(userId, payload)`
- `resetUserUiPrefs(userId)`

### 2. BaseStore

Foi adicionada uma nova camada de estado para preferências individuais:

- `state.userPrefs`

Foi adicionada a API:

- `BaseStore.userPrefs.key()`
- `BaseStore.userPrefs.get()`
- `BaseStore.userPrefs.set()`
- `BaseStore.userPrefs.patch()`
- `BaseStore.userPrefs.clear()`

Também foi criado evento específico:

- `base:user-prefs:changed`

## Compatibilidade preservada

Nesta parte, os consumidores atuais não foram migrados.

Isso significa:

- `BaseStore.prefs` continua existindo
- bootstrap visual ainda não foi alterado
- runtime de tema/cor ainda não foi alterado

Essas mudanças ficam reservadas para:

- Parte `2.10.3`

## Resultado esperado da Parte 2.10.2

Ao final desta parte:

- a infraestrutura por usuário existe
- a chave individual está operacional no contrato da store
- os consumidores atuais permanecem estáveis
- o sistema está pronto para migrar bootstrap e runtime na próxima parte
