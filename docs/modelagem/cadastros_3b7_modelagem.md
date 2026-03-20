# Sistema Visa - Etapa 3B - Parte 3B.7

## Modelagem Estrutural dos Campos por Tipo e Regras de Conversao

Status desta parte:
- Estrutural
- Sem execucao de interface
- Sem execucao de persistencia

Objetivo:
- Formalizar a modelagem futura do modulo Cadastros por tipo.
- Consolidar campos comuns, campos especificos, obrigatoriedades, entidades internas e regras de conversao.

## 1. Decisao estrutural da etapa

O modulo continua baseado em:
- uma entidade central `cadastros`
- classificacao por tipos administrativos

Porem, a partir desta parte, os tipos deixam de ser tratados apenas como rotulos operacionais.

Decisao:
- `cliente` e `fornecedor` permanecem como extensoes comportamentais da base comum
- `motorista` e `transportadora` passam a exigir estrutura complementar propria
- a interface futura nao deve permitir conversao livre entre tipos sem conclusao dos dados obrigatorios do novo tipo

## 2. Estrutura base comum do cadastro

Esta estrutura e comum a todos os tipos e representa o nucleo da entidade `cadastros`.

### 2.1 Identificacao

- `tipo_pessoa` (`PF` ou `PJ`)
- `nome`
- `razao_social`
- `nome_fantasia`
- `documento`
- `inscricao_estadual`
- `status`

Observacao:
- em `PF`, o campo dominante e `nome` com `CPF`
- em `PJ`, os campos dominantes sao `razao_social`, `nome_fantasia`, `CNPJ` e `inscricao_estadual`

### 2.2 Contato

- `contato`
- `telefone_fixo`
- `whatsapp`
- `celular`
- `email`

### 2.3 Endereco

- `cep`
- `endereco`
- `numero`
- `complemento`
- `bairro`
- `cidade`
- `estado`

### 2.4 Classificacao

- `tipos_associados`

### 2.5 Informacoes adicionais

- `observacoes`

### 2.6 Tags

- `tags`

Observacao:
- `tags` representam areas de interesse, produtos, categorias ou regioes de atuacao, dependendo do tipo

## 3. Tipos sem estrutura adicional propria

### 3.1 Cliente

Regra:
- utiliza apenas a estrutura base comum

Comportamento:
- pode existir como `PF` ou `PJ`
- usa `tags` como areas de interesse
- nao possui tabela estrutural adicional propria

### 3.2 Fornecedor

Regra:
- utiliza apenas a estrutura base comum

Comportamento:
- nasce com preferencia operacional em `PJ`, mas pode ser alterado para `PF`
- usa `tags` como areas de interesse
- nao possui tabela estrutural adicional propria

Observacao:
- `cliente` e `fornecedor` diferem por uso no sistema, nao por estrutura complementar

## 4. Tipos com estrutura adicional propria

### 4.1 Motorista

O cadastro de `motorista` sempre possui dados pessoais do motorista principal.

#### 4.1.1 Campos obrigatorios adicionais

- `cnh`

#### 4.1.2 Pessoa fisica do motorista

Sempre presente, mesmo quando o cadastro do motorista tambem possuir bloco juridico.

Campos:
- `nome`
- `cpf`
- `cnh`
- `status`

#### 4.1.3 Bloco juridico do motorista

Condicional.

So aparece quando o motorista tambem opera como pessoa juridica.

Campos:
- `razao_social`
- `nome_fantasia`
- `cnpj`
- `inscricao_estadual`

Regra:
- o bloco juridico nao substitui os dados da pessoa fisica
- ele complementa o cadastro

#### 4.1.4 Contato e endereco

O motorista usa a mesma estrutura base de:
- contato
- endereco

#### 4.1.5 Motorista secundario

Estrutura opcional.

Quando ativada, deve armazenar:
- dados pessoais
- dados de contato

Escopo atual da modelagem:
- `0..1` motorista secundario por cadastro de motorista

#### 4.1.6 Veiculos

Estrutura repetivel `1:N`.

Cada veiculo contem:
- `modelo`
- `placa`
- `placa_adicional`
- `tipo_carroceria`
- `metragem`
- `peso_carga`

#### 4.1.7 Rotas

Representadas por `tags` de regiao ou atuacao.

### 4.2 Transportadora

A `transportadora` reaproveita a logica estrutural do motorista, mas em escala maior.

#### 4.2.1 Pessoa juridica

Padrao operacional:
- `PJ` por padrao

Campos base dominantes:
- `razao_social`
- `nome_fantasia`
- `cnpj`
- `inscricao_estadual`

#### 4.2.2 Motoristas vinculados

Estrutura repetivel `1:N`.

Cada motorista vinculado deve suportar:
- dados pessoais
- dados de contato
- `cnh`

#### 4.2.3 Veiculos

Estrutura repetivel `1:N`.

Cada veiculo contem:
- `modelo`
- `placa`
- `placa_adicional`
- `tipo_carroceria`
- `metragem`
- `peso_carga`

#### 4.2.4 Rotas

Representadas por `tags` de regiao ou atuacao.

## 5. Regras de obrigatoriedade por tipo

### 5.1 Base comum

Obrigatorios para todo cadastro:
- `tipo_pessoa`
- `documento`
- `status`

Obrigatorio dominante conforme o tipo de pessoa:
- `PF`: `nome`
- `PJ`: `razao_social`

Obrigatorios operacionais comuns:
- ao menos um meio principal de contato
- endereco minimo utilizavel para operacao

### 5.2 Cliente / Fornecedor

Nao exigem obrigatorios complementares alem da base comum.

### 5.3 Motorista

Obrigatorios:
- dados da pessoa fisica do motorista principal
- `cnh`

Condicionais:
- bloco juridico somente se operar como `PJ`
- motorista secundario somente se ativado
- ao menos um veiculo quando a regra operacional futura assim exigir

### 5.4 Transportadora

Obrigatorios:
- estrutura juridica base
- ao menos um motorista vinculado
- ao menos um veiculo

Condicionais:
- campos adicionais conforme expansao futura de operacao

## 6. Regras de conversao entre tipos

A conversao entre tipos nao deve ser tratada como simples checkbox.

### 6.1 Regra geral

Se o novo tipo exigir estrutura adicional, o sistema deve:
- salvar o estado atual do cadastro base
- informar que a conversao depende da conclusao do novo tipo
- redirecionar para a tela de conclusao estrutural correspondente

### 6.2 Fluxo obrigatorio

Fluxo previsto:
1. usuario marca um novo tipo
2. sistema valida se o tipo novo exige dados complementares
3. se exigir, o sistema salva o cadastro atual
4. o sistema redireciona para a conclusao do novo tipo
5. a conversao so e considerada concluida quando os obrigatorios do novo tipo forem preenchidos

### 6.3 Restricao de conversao multipla

Regra:
- nao permitir conclusao simultanea de varios novos tipos estruturais na mesma acao

Exemplo:
- se um `cliente` for convertido para `motorista`, o usuario deve concluir `motorista` antes de tentar converter o mesmo cadastro para outro tipo estrutural adicional

Motivo:
- evitar redirecionamentos encadeados
- evitar pendencias estruturais concorrentes
- preservar consistencia operacional

## 7. Estruturas internas dependentes

As proximas partes de persistencia devem considerar que o modulo nao sera sustentado apenas pela tabela central.

Entidades complementares previstas:
- detalhes especificos de motorista
- motoristas vinculados
- veiculos vinculados
- tags vinculadas ao cadastro

Direcao tecnica recomendada para persistencia futura:
- `cadastros` permanece como raiz da entidade
- estruturas complementares devem referenciar `cadastro_id`
- repeticoes devem ser modeladas em relacoes separadas `1:N`

## 8. Impacto estrutural na persistencia futura

Esta parte nao cria banco nem altera repositores, mas define o que a persistencia futura precisa suportar.

### 8.1 A base atual nao e suficiente para o produto final

A estrutura atual suporta:
- dados centrais
- tipos associados

A estrutura atual nao suporta completamente:
- nome fantasia
- inscricao estadual
- contato separado do nome principal
- telefone fixo, whatsapp e celular como campos distintos
- tags estruturadas
- dados adicionais de motorista
- motorista secundario
- multiplos motoristas
- multiplos veiculos
- fluxo de conversao com conclusao obrigatoria por tipo

### 8.2 Consequencia para as proximas partes

Antes de ampliar interface final, sera necessario:
- definir onde cada campo novo vive
- definir tabelas complementares
- definir relacoes `1:1` e `1:N`
- ajustar o contrato de leitura e escrita do modulo

## 9. Decisoes consolidadas desta parte

Ficam formalmente consolidadas as seguintes decisoes:

- `cliente` e `fornecedor` usam apenas a estrutura base
- `motorista` e `transportadora` exigem estrutura complementar
- `motorista` pode combinar bloco `PF` obrigatorio com bloco `PJ` condicional
- `transportadora` e `PJ` por padrao
- conversao entre tipos exige fluxo guiado e conclusao obrigatoria
- nao deve existir conversao multipla estrutural simultanea
- veiculos e motoristas vinculados sao entidades repetiveis futuras

## 10. Uso desta modelagem nas proximas partes

Esta modelagem passa a ser a referencia obrigatoria para:
- desenho da persistencia complementar da etapa
- definicao de contratos do repositrio
- desenho das telas futuras de conversao e conclusao por tipo
- revisao das obrigatoriedades de formulario

Esta parte nao invalida a `3B.6`.

A `3B.6` permanece correta no escopo da `Opcao A`, mas deixa de ser considerada representacao final da tela completa de cadastro por tipo.
