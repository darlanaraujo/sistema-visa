# Sistema Visa — Etapa 2 — Correção 2.10 — Fechamento

## Situação

A Correção `2.10` está formalmente encerrada.

O objetivo da correção foi separar:

- configuração global do sistema
- preferências visuais individuais por usuário

Mantendo a arquitetura oficial:

`UI -> Store -> SysStore -> API -> MySQL`

## Partes executadas

### 2.10.1 — Mapeamento de escopo e contrato

Foi definida formalmente a separação entre:

- escopo global institucional
- escopo individual de preferências visuais

### 2.10.2 — Infra de persistência por usuário

Foi criada a infraestrutura para persistência individual por usuário autenticado, com contrato conceitual:

- `user_ui_prefs:<user_id>`

### 2.10.3 — Bootstrap e runtime visual

Foi migrada a leitura e aplicação de tema/cor para o escopo do usuário autenticado, preservando:

- identidade institucional global
- first paint estável
- compatibilidade transitória dos consumidores visuais

Também foi validado em teste funcional multiusuário que:

- tema e cor permanecem individuais por usuário
- favicon permanece global
- dados operacionais do sistema continuam compartilhados

## Resultado consolidado

Ao final da Correção `2.10`:

- tema e cor passaram a respeitar o usuário autenticado
- identidade institucional permaneceu global
- o contrato da store foi separado corretamente por escopo
- a validação multiusuário confirmou o comportamento esperado

## Encerramento da correção

A Correção `2.10` deve ser considerada concluída com as partes:

- `2.10.1`
- `2.10.2`
- `2.10.3`

As derivações `2.10.4` e `2.10.5` não seguem como continuação operacional desta correção.

## Direcionamento futuro

Foi identificado um aprimoramento necessário de UX no módulo Ferramentas:

- separar visualmente no painel o que é configuração global
- separar visualmente o que é preferência individual do usuário
- deixar explícito no próprio formulário o escopo de cada bloco

Esse ponto não será tratado agora.

Decisão:

- registrar como melhoria futura
- executar oportunamente na etapa já prevista para refatoração visual do módulo Ferramentas

## Observação para o futuro redesenho do módulo Ferramentas

Quando o módulo Ferramentas for revisitado visualmente, a UX deverá refletir a modelagem já consolidada pela Correção `2.10`.

Recomendação mínima:

- seção de configuração global do sistema
- seção de aparência do usuário atual
- textos claros de escopo
- ações separadas para salvar bloco global e bloco individual, se fizer sentido no desenho final
