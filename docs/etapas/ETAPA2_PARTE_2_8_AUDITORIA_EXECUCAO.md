# Sistema Visa — Etapa 2 — Parte 2.8 — Plano Operacional de Execução

## Objetivo

Definir a execução prática da auditoria final da Etapa 2, cobrindo infraestrutura, comportamento funcional e aderência arquitetural do sistema após a migração de persistência e autenticação para MySQL.

## Ambiente de teste

Pré-condições obrigatórias:

- XAMPP ativo
- MySQL ativo
- banco `sistema_visa` existente
- migrations `001` a `004` aplicadas
- seed inicial carregado
- endpoints `store_*` acessíveis
- navegador com DevTools disponível
- sessão previamente encerrada antes do ciclo principal de teste

Usuário de validação:

- e-mail: `admin@sistema.local`
- senha: `admin123`

## Sequência operacional da auditoria

### Bloco 1 — Infraestrutura e pré-check

Objetivo:

Confirmar que o ambiente permite validar o sistema real.

Verificações:

- banco `sistema_visa` acessível
- tabelas `companies`, `store` e `users` existentes
- conexão PDO funcional no ambiente real
- endpoints `store_get`, `store_set` e `store_remove` respondendo

Evidências esperadas:

- confirmação de acesso ao banco
- resposta válida dos endpoints
- ausência de erro 500 por infraestrutura

Bloqueios possíveis:

- MySQL inativo
- credenciais divergentes
- API indisponível

### Bloco 2 — Persistência oficial e contrato de arquitetura

Objetivo:

Validar que a persistência oficial do sistema está operando exclusivamente no fluxo arquitetural definido.

Verificações:

- leitura ocorre via `SysStore -> ApiDriver -> API -> MySQL`
- gravação ocorre via `SysStore -> ApiDriver -> API -> MySQL`
- remoção ocorre via `SysStore -> ApiDriver -> API -> MySQL`
- não existe módulo acessando persistência diretamente

Verificação de contrato de arquitetura:

Confirmar que o fluxo real permanece:

`UI -> Store do modulo -> SysStore -> ApiDriver -> API PHP -> MySQL`

Nenhum módulo deve acessar a persistência diretamente fora desse fluxo.

Evidências esperadas:

- resposta válida dos endpoints `store_*`
- escrita real confirmada na tabela `store`
- remoção real confirmada na tabela `store`
- inspeção técnica dos pontos principais sem quebra do contrato arquitetural

Bloqueios possíveis:

- acesso direto indevido a persistência
- escrita não refletida no banco
- fallback não previsto

### Bloco 3 — Autenticação e sessão

Objetivo:

Validar a autenticação real em banco e a segurança mínima da sessão.

Verificações:

- login com `admin@sistema.local`
- senha `admin123`
- redirect para dashboard
- sessão `auth_user` criada corretamente
- logout destruindo a sessão
- idle timeout preservado

Verificação de sessão:

Confirmar que após login:

- `auth_user` existe na sessão
- `Session::regenerate()` foi aplicado
- logout destrói corretamente a sessão

Evidências esperadas:

- autenticação bem-sucedida com usuário seeded
- payload de `auth_user` compatível com o sistema privado
- acesso bloqueado após logout

Bloqueios possíveis:

- usuário seeded não autentica
- sessão não criada
- sessão não regenerada
- logout não invalida a autenticação

### Bloco 4 — Validação funcional dos módulos principais

Objetivo:

Validar que os módulos centrais operam corretamente já apoiados na nova arquitetura.

Módulos mínimos:

- Ferramentas
- Financeiro Dashboard
- Contas a Pagar
- Contas a Receber
- Relatórios

Checklist funcional:

Ferramentas:

- criar item
- editar item
- excluir item
- confirmar persistência no banco

Financeiro:

- criar CP
- criar CR
- confirmar reload com dados persistidos
- confirmar gráficos do dashboard quando houver dados
- confirmar relatórios refletindo os dados persistidos sem ação manual indevida

Evidências esperadas:

- CRUD funcional refletido em banco
- telas sem erro de bootstrap
- dados persistidos reaparecendo após reload

Bloqueios possíveis:

- consumidor ainda dependente de estado implícito
- tela não recompõe dados persistidos
- gráfico não renderiza com dados existentes

### Bloco 5 — Bootstrap assíncrono e `localStorage`

Objetivo:

Fechar a auditoria validando estabilidade de inicialização e ausência de persistência operacional paralela.

Verificações:

- bootstrap privado sem erro visível
- hidratação das stores antes do uso funcional
- first paint estável
- preferências visuais aplicadas corretamente
- ausência de persistência operacional ativa em `localStorage`

Uso aceitável de `localStorage`:

- apenas suporte técnico de first paint/bootstrap visual

Uso não aceitável:

- fonte de verdade de negócio
- fallback operacional de persistência

Evidências esperadas:

- abertura estável da área privada
- ausência de concorrência visível de inicialização
- ausência de fallback operacional fora do banco

Bloqueios possíveis:

- módulo dependente de boot implícito
- leitura operacional residual em `localStorage`
- inconsistência entre first paint e estado hidratado

## Evidências a registrar na execução

- arquivos e pontos inspecionados
- testes executados em cada bloco
- respostas relevantes da API
- evidência de escrita/leitura/remoção na tabela `store`
- confirmação do login seeded
- confirmação da sessão `auth_user`
- falhas encontradas, se houver

## Critério de aprovação final da Etapa 2

A Etapa 2 poderá ser encerrada somente se as três camadas forem aprovadas:

### 1. Infraestrutura

- MySQL ativo
- migrations aplicadas
- API funcionando

### 2. Comportamento funcional

- login funcional
- Ferramentas funcional
- Financeiro funcional
- persistência refletida corretamente

### 3. Arquitetura

- UI desacoplada da persistência
- fluxo Store -> SysStore -> ApiDriver -> API -> MySQL preservado
- ausência de persistência operacional ativa em `localStorage`

## Próxima ação

Após este registro, a próxima ação formal é:

- execução da auditoria da Parte `2.8`

Entrega esperada da execução:

- evidências coletadas
- resultado por bloco
- conclusão técnica da Etapa 2
