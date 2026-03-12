# Sistema Visa — Etapa 2 — Status Formal

## Contexto

A Etapa 2 teve como objetivo migrar a persistência e a autenticação do sistema para infraestrutura real em MySQL, preservando a arquitetura oficial da aplicação.

Fluxo arquitetural consolidado ao final da etapa:

`UI -> Store do modulo -> SysStore -> ApiDriver -> API store_* -> MySQL`

## Partes concluídas

- `2.1` — schema inicial do banco
- `2.1a` — correção estrutural da tabela `store`
- `2.2` — API `store_*`
- `2.3` — SysStore via ApiDriver + núcleo consumidor mínimo
- `2.3a` — preparação do ambiente MySQL
- `2.3b` — correção de compatibilidade SQL no `store_set`
- `2.4` — adaptação de consumidores + estabilização do bootstrap
- `2.5` — consolidação do bootstrap assíncrono
- `2.6` — login real em banco
- `2.9` — estabilização pós-entrega de personalização, tema e sessão
- `2.10.1` — mapeamento de escopo e contrato para preferências visuais por usuário
- `2.10.2` — infra de persistência por usuário
- `2.10.3` — bootstrap e runtime visual

## Registro formal da Parte 2.7

### Situação

A Parte `2.7` foi analisada e formalmente registrada como `não executada`.

### Tema original da Parte 2.7

Importação opcional de dados legados do módulo Ferramentas que existiam em `localStorage`.

### Motivos da não execução

- não existem dados legados críticos que precisem ser preservados
- os dados antigos de Ferramentas podem ser recriados manualmente com baixo custo
- a implementação exigiria mecanismo temporário de importação
- a implementação exigiria controle de execução e bloqueio de reimportação
- isso adicionaria complexidade técnica sem benefício proporcional

### Decisão formal

- a Parte `2.7` foi avaliada
- a execução foi considerada desnecessária
- a não execução não compromete a arquitetura nem o funcionamento do sistema
- os dados de Ferramentas serão recriados manualmente, se necessário

## Situação atual da Etapa 2

Com as Partes `2.6`, `2.9`, `2.10.1`, `2.10.2` e `2.10.3` concluídas, o sistema já possui:

- persistência oficial via MySQL
- `SysStore` operando via API
- bootstrap assíncrono consolidado na área privada
- autenticação real em banco pela tabela `users`
- estabilização corretiva do first paint privado
- sincronização reforçada do tema entre navegações
- remoção do logout indevido no cliente durante navegação privada
- separação formal de contrato entre configuração global e preferência visual individual
- infraestrutura de store preparada para preferências visuais por usuário
- leitura e aplicação visual migradas para escopo individual com fallback controlado
- Correção `2.10` formalmente encerrada

## Registro formal da Parte 2.10.1

### Situação

A Parte `2.10.1` foi executada como etapa de mapeamento de escopo e contrato, sem alteração operacional de persistência.

### Decisão consolidada

- identidade institucional permanece global
- tema, cor de acento e preferências visuais passam a ser tratadas como escopo por usuário
- a arquitetura oficial permanece inalterada

O registro formal da Parte `2.10.1` está documentado em:

- [ETAPA2_PARTE_2_10_1_ESCOPO_E_CONTRATO.md](/Applications/XAMPP/xamppfiles/htdocs/sistema-visa/docs/etapas/ETAPA2_PARTE_2_10_1_ESCOPO_E_CONTRATO.md)

## Registro formal da Parte 2.10.2

### Situação

A Parte `2.10.2` foi executada como preparação de infraestrutura para persistência individual por usuário, sem migrar ainda bootstrap e runtime visual.

### Entrega consolidada

- `tools_sys_prefs_v2` permaneceu global
- a chave `user_ui_prefs:<user_id>` foi preparada na infraestrutura
- `BaseStore` passou a expor contrato específico para preferências individuais
- os consumidores atuais foram preservados para evitar regressão prematura

O registro formal da Parte `2.10.2` está documentado em:

- [ETAPA2_PARTE_2_10_2_INFRA_POR_USUARIO.md](/Applications/XAMPP/xamppfiles/htdocs/sistema-visa/docs/etapas/ETAPA2_PARTE_2_10_2_INFRA_POR_USUARIO.md)

## Registro formal da Parte 2.10.3

### Situação

A Parte `2.10.3` foi executada para migrar bootstrap e runtime visual para o escopo individual do usuário, sem mover a configuração institucional global.

### Entrega consolidada

- runtime visual passou a compor dados globais e individuais
- toggle de tema passou a persistir em `BaseStore.userPrefs`
- preview de relatórios passou a consultar o contrato individual
- first paint permaneceu apoiado em cache técnico combinado

O registro formal da Parte `2.10.3` está documentado em:

- [ETAPA2_PARTE_2_10_3_BOOTSTRAP_RUNTIME_VISUAL.md](/Applications/XAMPP/xamppfiles/htdocs/sistema-visa/docs/etapas/ETAPA2_PARTE_2_10_3_BOOTSTRAP_RUNTIME_VISUAL.md)

## Fechamento da Correção 2.10

### Situação

A Correção `2.10` foi encerrada com sucesso após validação técnica e validação funcional multiusuário.

### Escopo concluído

- separação formal entre configuração global e preferência visual individual
- criação da infraestrutura de persistência por usuário
- migração do bootstrap e runtime visual para respeitar o usuário autenticado

### Validação funcional consolidada

Foi confirmado que:

- cor e tema permanecem individuais por usuário
- favicon permanece global
- dados operacionais continuam compartilhados entre usuários da mesma empresa

### Encaminhamento futuro

A melhoria de UX do módulo Ferramentas para explicitar melhor o escopo global vs individual fica registrada como item futuro da etapa prevista de refatoração visual desse módulo, fora do escopo da Correção `2.10`.

O fechamento formal da Correção `2.10` está documentado em:

- [ETAPA2_CORRECAO_2_10_FECHAMENTO.md](/Applications/XAMPP/xamppfiles/htdocs/sistema-visa/docs/etapas/ETAPA2_CORRECAO_2_10_FECHAMENTO.md)

## Registro formal da Parte 2.9

### Situação

A Parte `2.9` foi aberta como correção pós-entrega da Etapa 2 e executada com foco em estabilização visual e de navegação.

### Escopo executado

- alinhamento do bootstrap visual da área privada
- reforço da sincronização do tema dark/light
- investigação objetiva do retorno indevido ao login

### Diagnóstico consolidado

- a divergência visual estava ligada à ordem de bootstrap no ambiente privado
- a sessão não estava expirando por timeout
- o retorno ao login era causado por logout cliente em `pagehide` durante a navegação entre páginas

### Decisão técnica

- a camada `Session/AuthMiddleware` foi investigada e preservada
- a correção foi concentrada no bootstrap visual e no comportamento cliente da área privada

O registro formal da Parte `2.9` está documentado em:

- [ETAPA2_PARTE_2_9_ESTABILIZACAO.md](/Applications/XAMPP/xamppfiles/htdocs/sistema-visa/docs/etapas/ETAPA2_PARTE_2_9_ESTABILIZACAO.md)
