# Sistema Visa - Etapa 3B - Parte 3B.8

## Modelagem de Persistencia das Extensoes por Tipo

Status desta parte:
- Estrutural
- Sem execucao de interface
- Sem execucao de persistencia

Objetivo:
- Transformar a modelagem da `3B.7` em desenho de persistencia.
- Definir a expansao do modelo atual sem quebrar a raiz `cadastros`.
- Preservar consulta global da entidade em todo o sistema.

## 1. Diretriz estrutural consolidada

Direcao fechada para esta parte:
- modelo hibrido
- campos comuns permanecem em `cadastros`
- extensoes apenas para dados especificos e repetiveis
- nao criar `cadastro_base_contato`
- nao criar `cadastro_base_endereco`
- motorista secundario modelado como `1:N`
- tags em estrutura relacional propria

Decisao de leitura global:
- `cadastros` continua sendo a origem principal de consulta transversal do sistema
- filtros por tipo sao refinamentos
- tipos nao podem fragmentar a leitura da entidade

## 2. Principio de persistencia

Principio central:

- `cadastros` continua sendo a raiz da entidade
- toda extensao referencia `cadastro_id`
- campos comuns vivem na raiz
- dados especificos vivem em tabelas complementares
- estruturas repetiveis vivem em relacoes `1:N`

## 3. Evolucao da tabela central `cadastros`

Para suportar a estrutura base definida na `3B.7`, a tabela `cadastros` deve absorver os campos comuns que hoje ainda nao existem.

### 3.1 Campos comuns mantidos na raiz

Identificacao:
- `tipo_pessoa`
- `nome`
- `razao_social`
- `nome_fantasia`
- `documento`
- `inscricao_estadual`
- `status`

Contato:
- `contato`
- `telefone_fixo`
- `whatsapp`
- `celular`
- `email`

Endereco:
- `cep`
- `endereco`
- `numero`
- `complemento`
- `bairro`
- `cidade`
- `estado`

Informacoes adicionais:
- `observacoes`

Observacao:
- os campos antigos mais genericos devem ser absorvidos ou substituidos pelo desenho novo da base comum
- a adaptacao exata de nomenclatura devera ser fechada na parte de migration

### 3.2 Justificativa

Esses campos permanecem em `cadastros` porque:
- sao comuns a todos os tipos
- serao usados em consulta transversal do sistema
- serao consumidos por modulos como Financeiro sem necessidade de join estrutural obrigatorio

## 4. Tabelas complementares previstas

As tabelas abaixo suportam apenas o que e especifico por tipo ou repetivel.

### 4.1 `cadastro_motorista_detalhes`

Finalidade:
- armazenar dados especificos do tipo `motorista`

Relacao:
- `1:1` com `cadastros`

Campos conceituais:
- `id`
- `cadastro_id`
- `cnh`
- `created_at`
- `updated_at`

Regra:
- existe somente quando o cadastro possui tipo `motorista`

### 4.2 `cadastro_motoristas_vinculados`

Finalidade:
- armazenar motoristas adicionais vinculados ao cadastro principal

Relacao:
- `1:N` com `cadastros`

Uso previsto:
- motorista secundario de cadastro `motorista`
- motoristas vinculados de cadastro `transportadora`

Campos conceituais:
- `id`
- `cadastro_id`
- `nome`
- `cpf`
- `cnh`
- `contato`
- `telefone_fixo`
- `whatsapp`
- `celular`
- `email`
- `principal`
- `created_at`
- `updated_at`

Regra:
- o motorista principal continua no cadastro raiz
- os adicionais vivem nesta relacao

### 4.3 `cadastro_veiculos`

Finalidade:
- armazenar veiculos vinculados ao cadastro principal

Relacao:
- `1:N` com `cadastros`

Campos conceituais:
- `id`
- `cadastro_id`
- `modelo`
- `placa`
- `placa_adicional`
- `tipo_carroceria`
- `metragem`
- `peso_carga`
- `created_at`
- `updated_at`

Uso previsto:
- veiculos de motorista
- veiculos de transportadora

### 4.4 `cadastro_tags`

Finalidade:
- catalogo relacional de tags

Campos conceituais:
- `id`
- `nome`
- `slug`
- `status`
- `created_at`
- `updated_at`

### 4.5 `cadastro_tag_rel`

Finalidade:
- relacao entre cadastro e tags

Relacao:
- `N:N` entre `cadastros` e `cadastro_tags`

Campos conceituais:
- `id`
- `cadastro_id`
- `tag_id`
- `created_at`

Uso previsto:
- areas de interesse
- produtos
- categorias
- rotas e regioes de atuacao

## 5. Relacoes consolidadas

Relacoes principais da entidade:

- `cadastros` -> `cadastro_tipo_rel` = `1:N`
- `cadastros` -> `cadastro_motorista_detalhes` = `1:1`
- `cadastros` -> `cadastro_motoristas_vinculados` = `1:N`
- `cadastros` -> `cadastro_veiculos` = `1:N`
- `cadastros` -> `cadastro_tag_rel` = `1:N`
- `cadastro_tags` -> `cadastro_tag_rel` = `1:N`

## 6. Estrategia por tipo

### 6.1 Cliente

Persistencia:
- usa apenas `cadastros`
- usa `cadastro_tipo_rel`
- usa `cadastro_tag_rel` quando houver tags

Nao exige tabela complementar propria.

### 6.2 Fornecedor

Persistencia:
- usa apenas `cadastros`
- usa `cadastro_tipo_rel`
- usa `cadastro_tag_rel` quando houver tags

Nao exige tabela complementar propria.

### 6.3 Motorista

Persistencia:
- usa `cadastros`
- usa `cadastro_tipo_rel`
- usa `cadastro_motorista_detalhes`
- usa `cadastro_motoristas_vinculados` quando houver secundario
- usa `cadastro_veiculos`
- usa `cadastro_tag_rel`

### 6.4 Transportadora

Persistencia:
- usa `cadastros`
- usa `cadastro_tipo_rel`
- usa `cadastro_motoristas_vinculados`
- usa `cadastro_veiculos`
- usa `cadastro_tag_rel`

Observacao:
- `transportadora` nao precisa de tabela `1:1` propria neste momento, porque sua diferenciacao estrutural esta principalmente nas relacoes repetiveis

## 7. Regras de conversao e persistencia

Conversao entre tipos exige persistencia progressiva.

### 7.1 Regra principal

Se um tipo novo exigir estrutura adicional:
- salva-se primeiro a base de `cadastros`
- registra-se o tipo associado
- conclui-se a extensao obrigatoria correspondente

### 7.2 Consequencia estrutural

O sistema futuro precisara distinguir:
- cadastro salvo na base
- extensao do tipo ainda pendente
- conversao estrutural concluida

Direcao recomendada:
- tratar conclusao de extensao como estado operacional do cadastro
- nao assumir que a simples presenca do tipo implica completude estrutural

## 8. Contrato futuro do `CadastroRepository`

O repositrio futuro deve continuar orientado por consulta global da entidade.

### 8.1 Principio de consumo

Nenhum modulo consumidor deve depender de consulta separada por tipo como origem principal.

Regra:
- a busca operacional parte da base unica de `cadastros`
- o tipo e atributo de classificacao
- joins de extensao entram apenas quando necessarios

### 8.2 Capacidades futuras do repositorio

Leituras principais:
- listar cadastros globalmente
- buscar por documento
- buscar por nome/razao social
- filtrar por status
- filtrar por tipo como refinamento

Leituras detalhadas:
- carregar cadastro com tipos associados
- carregar extensoes por tipo
- carregar veiculos vinculados
- carregar motoristas vinculados
- carregar tags

Escritas:
- salvar base de `cadastros`
- sincronizar tipos
- salvar detalhes de motorista
- salvar motoristas vinculados
- salvar veiculos
- sincronizar tags

### 8.3 Impacto na leitura transversal

Exemplo de consumo esperado:
- Financeiro deve conseguir consultar todos os cadastros ativos a partir da base unica
- so depois, se necessario, aplicar filtro por `cliente`, `fornecedor`, `motorista` ou `transportadora`

Consequencia:
- a modelagem nao pode obrigar o sistema a iniciar consulta em tabela especifica de tipo

## 9. O que nao deve ser feito

Para manter a coerencia da etapa, ficam descartadas nesta modelagem:

- tabelas separadas para `clientes`, `fornecedores`, `motoristas` e `transportadoras` como raizes independentes
- fragmentacao da leitura principal por tipo
- externalizacao da base comum em `cadastro_base_contato`
- externalizacao da base comum em `cadastro_base_endereco`
- modelagem de motorista secundario como campo fixo `1:1`

## 10. Decisoes consolidadas desta parte

Ficam consolidadas as seguintes decisoes:

- `cadastros` continua raiz da entidade
- os campos comuns permanecem na tabela central
- extensoes existem apenas para dados especificos ou repetiveis
- `cliente` e `fornecedor` nao ganham tabela propria
- `motorista` ganha extensao `1:1` para `cnh`
- motoristas adicionais ficam em relacao `1:N`
- veiculos ficam em relacao `1:N`
- tags passam para estrutura relacional propria
- o repositrio deve preservar consulta global da entidade

## 11. Uso desta modelagem nas proximas partes

Esta modelagem passa a ser a referencia obrigatoria para:
- novas migrations do modulo Cadastros
- evolucao do `CadastroRepository`
- definicao dos contratos de leitura e escrita
- desenho das telas finais de cadastro por tipo
- implementacao futura das conversoes guiadas

Esta parte nao altera o que foi entregue na `3B.6`.

A `3B.6` permanece valida no escopo da `Opcao A`, mas a persistencia final do modulo agora deve seguir esta arquitetura complementar.
