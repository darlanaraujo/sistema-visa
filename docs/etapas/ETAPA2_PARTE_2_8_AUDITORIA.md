# Sistema Visa — Etapa 2 — Parte 2.8 — Plano de Auditoria Final

## Objetivo

Executar a auditoria final da Etapa 2 para validar, de forma estrutural e funcional, que a nova arquitetura de persistência e autenticação está operando corretamente no sistema.

## Escopo da auditoria

### 1. Persistência oficial via MySQL

Validar que:

- leituras da aplicação ocorrem via API `store_*`
- gravações da aplicação chegam corretamente à tabela `store`
- remoções ocorrem corretamente na persistência oficial
- o fluxo `SysStore -> ApiDriver -> API -> MySQL` está íntegro

### 2. Ausência de persistência operacional em `localStorage`

Validar que:

- não existe `localStorage` como fonte de verdade operacional
- qualquer uso remanescente de `localStorage` esteja restrito a bootstrap técnico de first paint
- não exista fallback operacional híbrido entre navegador e banco

### 3. Bootstrap assíncrono consolidado

Validar que:

- a área privada inicializa em ordem previsível
- `BaseStore`, `FerStore` e `FinStore` hidratam antes do uso funcional dos módulos
- os módulos principais aguardam o bootstrap privado
- não existem leituras críticas de estado antes da hidratação

### 4. Login real em banco

Validar que:

- o login usa a tabela `users`
- o usuário seeded autentica corretamente
- a senha é validada por bcrypt
- a sessão `auth_user` é criada com payload compatível com o consumo atual
- a regeneração de sessão após login está funcionando
- logout e idle timeout continuam válidos

### 5. Módulos principais no fluxo oficial

Validar ao menos os módulos centrais já adaptados:

- Ferramentas
- Financeiro Dashboard
- Contas a Pagar
- Contas a Receber
- Relatórios

Confirmar que esses módulos:

- leem estado das stores oficiais
- persistem via `SysStore`
- não dependem de persistência local operacional

## Checklist de auditoria

### A. Banco e API

- confirmar banco `sistema_visa` ativo
- confirmar tabelas `companies`, `store` e `users`
- confirmar endpoints `store_get`, `store_set` e `store_remove` respondendo com sucesso
- confirmar escrita real na tabela `store`

### B. Login

- realizar login com `admin@sistema.local`
- usar senha `admin123`
- confirmar redirect para dashboard
- confirmar sessão `auth_user` válida
- confirmar logout funcional

### C. Ferramentas

- criar item em Ferramentas
- editar item
- excluir item
- confirmar persistência em banco

### D. Financeiro

- criar CP
- criar CR
- confirmar carregamento após reload
- confirmar gráficos do dashboard quando houver dados
- confirmar relatórios refletindo os dados persistidos sem exigir ação manual indevida

### E. Bootstrap

- confirmar ausência de erro de boot na área privada
- confirmar first paint estável
- confirmar preferências visuais aplicadas corretamente
- confirmar ausência de concorrência visível de inicialização

### F. LocalStorage

- inspecionar pontos remanescentes
- confirmar que não há persistência operacional ativa fora do banco

## Critérios de aprovação

A Parte `2.8` poderá ser aprovada se:

- o login real em banco estiver funcional
- a persistência via MySQL estiver confirmada
- os módulos principais estiverem operando no fluxo oficial
- não houver fallback operacional em `localStorage`
- o bootstrap assíncrono estiver estável

## Evidências esperadas

Ao executar a auditoria, registrar:

- arquivos e pontos inspecionados
- testes funcionais realizados
- respostas da API quando relevantes
- evidência de persistência na tabela `store`
- confirmação do login seeded
- eventuais falhas encontradas

## Resultado esperado da auditoria

Ao final da Parte `2.8`, a Etapa 2 deverá estar formalmente encerrada com validação da nova arquitetura de:

- persistência
- bootstrap assíncrono
- autenticação real em banco
