# Sistema Visa - Etapa 4 - Parte 4.1

## Modelagem Estrutural do Modulo Lotes

Status desta parte:
- Estrutural
- Sem execucao de interface
- Sem execucao de persistencia

Objetivo:
- Formalizar o contrato tecnico do modulo Lotes antes de qualquer implementacao de banco, repositorio, store ou interface.
- Consolidar em linguagem executavel as decisoes estruturais da Etapa 4.
- Eliminar ambiguidade de modelagem antes da Parte 4.2.

## 1. Principio estrutural obrigatorio

Direcao central do modulo:

- `lote` e processo com estoque interno proprio

Consequencias obrigatorias:
- `lote` nao e estoque global
- `lote` nao e catalogo de produtos
- `lote` nao e cadastro simples
- o processo nasce a partir de uma compra confirmada
- os itens internos existem apenas para resolver a operacao do proprio lote
- a disponibilidade dos itens nao pode ser lida como estoque global do sistema

## 2. Regra de persistencia da etapa

Direcao obrigatoria:
- todos os dados do modulo Lotes serao persistidos em banco
- e proibido o uso de `LocalStorage`, `SessionStorage` ou similares para dados de dominio
- toda leitura e escrita do modulo deve ocorrer via backend e persistencia real

Consequencia estrutural:
- nenhuma decisao futura da etapa pode introduzir armazenamento local para estado de dominio

## 3. Nascimento do lote no sistema

Regra de entrada:
- o lote so nasce no sistema apos compra confirmada

Nao entra no sistema:
- proposta recebida
- estudo interno
- negociacao recusada

Entra no sistema:
- lote comprado
- processo assumido como real pela empresa

Consequencia estrutural:
- a entidade `lote` representa apenas processo real assumido operacionalmente
- etapas anteriores a compra nao fazem parte do escopo do modulo

## 4. Entidade central `lote`

O lote e a entidade raiz do modulo e concentra a leitura do processo.

### 4.1 Identificacao do processo

Campos conceituais obrigatorios:
- `fornecedor_id`
- `numero_processo`
- `titulo_lote`
- `descricao_resumida`
- `descricao_operacional`
- `tipo_macro_lote`
- `data_compra`
- `status_macro`
- `etapa_timeline`
- `observacoes_gerais`

Regra consolidada:
- `seguradora` e tratada como `fornecedor`
- nao existe entidade separada para seguradora
- `seguradora`, `origem da compra` e `vendedor do lote` apontam para o mesmo vinculo do cadastro central

### 4.2 Valores do processo

Blocos de custo do lote:
- `valor_original_lote`
- `valor_depreciado`
- `valor_pago_compra`
- `despesas_local`
- `valor_frete`
- `valor_documento_transporte`
- `outros_custos`
- `custo_total`

Regras obrigatorias:
- `custo_total` e a soma dos custos registrados
- o custo e sempre tratado no nivel do lote
- nao existe controle contabil por item
- itens possuem apenas valores de referencia

### 4.3 Local de armazenagem

Bloco interno do lote:
- `nome_local`
- `nome_contato`
- `telefone`
- `email`
- `endereco`
- `cidade`
- `estado`
- `observacoes_local`

Regra obrigatoria:
- o local de armazenagem pertence ao lote
- ele nao nasce como cadastro global reutilizavel nesta etapa

### 4.4 Dados operacionais de frete

Campos conceituais:
- `tipo_transporte`
- `motorista_id`
- `transportadora_id`
- `veiculo_referencia`
- `agenciador`
- `valor_frete`
- `documento_transporte`
- `data_contratacao`
- `data_agendamento`
- `data_coleta`
- `data_entrega`
- `observacoes_logisticas`

Regra obrigatoria:
- frete nao nasce como modulo independente nesta etapa
- frete e registro interno do processo
- quando houver motorista ou transportadora cadastrados, o lote mantem vinculo com esses cadastros

## 5. Timeline do lote

A timeline e a estrutura oficial de controle operacional do processo.

Macro etapas obrigatorias:
- `compra_confirmada`
- `liberacao`
- `coleta`
- `transporte`
- `recebido`
- `venda`
- `encerrado`

Cada macro etapa aceita checklist interno.

Regras estruturais:
- a timeline nao e decorativa
- a timeline representa o estado real do processo
- o avanco depende da conclusao da etapa atual
- nao deve existir salto livre entre etapas

Diferenca estrutural:
- `macro etapa` = estado operacional principal do lote
- `checklist interno` = condicoes operacionais que validam a conclusao da macro etapa

## 6. Status macro do dashboard

Os status macro sao leitura resumida e derivada do processo.

Enums obrigatorios:
- `em_transito`
- `em_estoque`
- `finalizado`

Mapeamento esperado:
- `liberacao`, `coleta` ou `transporte` -> `em_transito`
- `recebido` com itens disponiveis -> `em_estoque`
- todos os itens resolvidos -> `finalizado`

Regra obrigatoria:
- status macro nao e trilha paralela
- dashboard e timeline nao podem competir entre si

## 7. Conteudo interno do lote

O lote possui dois niveis de representacao do conteudo.

### 7.1 Nivel macro do lote

Campos conceituais:
- `descricao_macro`
- `tipo_controle_macro`
- `quantidade_total_macro`
- `valor_referencia_macro`
- `valor_venda_referencia_macro`

Regra:
- este nivel e apenas visao resumida do processo

### 7.2 Lista de itens do lote

Campos conceituais de cada item:
- `descricao_item`
- `tipo_controle_item`
- `quantidade_total`
- `quantidade_disponivel`
- `quantidade_baixada`
- `quantidade_vendida`
- `custo_unitario_referencia`
- `custo_total_referencia`
- `valor_venda_unitario_sugerido`
- `valor_venda_total_sugerido`
- `tags_item`
- `observacoes_item`
- `status_item`

Regras obrigatorias:
- item pode ser controlado por `unidade` ou `kg`
- item pertence ao lote
- item nao entra em estoque global
- disponibilidade e sempre local ao processo

## 8. Baixa do item

Baixa e operacao diferente de venda.

Baixa e:
- movimento operacional
- retirada de disponibilidade do item
- operacao sem exigencia de venda
- operacao sem geracao obrigatoria de CR

Estrutura minima da baixa:
- `item_id`
- `quantidade_baixada`
- `motivo_baixa`
- `observacao`
- `data`
- `responsavel`

Regras obrigatorias:
- baixa parcial e permitida
- baixa total e permitida
- item encerra quando `quantidade_disponivel = 0`
- lote encerra quando todos os itens tiverem `quantidade_disponivel = 0`

## 9. Venda dentro do lote

A venda do lote e operacao interna da pagina do processo.

Cabecalho da venda:
- `cliente_id`
- `forma_pagamento`
- `data_venda`
- `observacao_venda`
- `valor_total_venda`

Itens da venda:
- `item_id`
- `quantidade_vendida`
- `valor_unitario_vendido`
- `valor_total_item_vendido`

Regras obrigatorias:
- venda afeta a disponibilidade do item
- venda gera baixa correspondente
- venda gera ordem de venda propria
- venda gera CR no Financeiro
- o CR deve referenciar a ordem de venda

Consequencia estrutural:
- o Financeiro nao se torna origem da venda
- a origem da operacao permanece no lote

## 10. Regra financeira do modulo

Direcao obrigatoria:
- o sistema nao faz custo contabil por item
- o sistema nao rateia custo do lote entre itens
- o custo e sempre tratado no nivel do lote
- itens possuem apenas valores referenciais

Baixa sem venda:
- nao gera receita
- nao recalcula custo unitario
- nao altera a formula do resultado financeiro
- nao impede lucro posterior do lote

Resultado do lote:
- `lucro_prejuizo_parcial = total_vendido - custo_total`

Essa simplificacao e estrutural e intencional.

## 11. Movimentacoes do lote

O lote deve possuir historico oficial do processo.

Eventos esperados:
- custo adicionado
- mudanca de etapa
- frete contratado
- item baixado
- venda registrada
- anexo importante
- alteracao relevante do processo

Regra estrutural obrigatoria:
- o historico nasce como estrutura oficial do processo
- o historico deve ser cronologico
- o historico deve ser orientado por eventos reais
- o historico deve servir a rastreabilidade e auditoria

Resposta formal da parte:
- sim, o historico nasce como estrutura oficial do processo

## 12. Cruzamentos inteligentes

As tags do sistema sao globais e reutilizaveis.

Elas devem permitir cruzamentos entre:
- clientes
- motoristas
- transportadoras
- lotes
- itens do lote

### 12.1 Motoristas e rotas

O lote deve cruzar:
- cidade do lote
- estado do lote
- rota do lote
- tags de rota de motoristas e transportadoras

Objetivo:
- sugerir motoristas ou transportadoras compativeis

### 12.2 Clientes e interesses

O lote e seus itens devem cruzar:
- tags do lote
- tags dos itens
- tags de interesse de clientes

Objetivo:
- sugerir possiveis clientes compradores

Regra operacional:
- a indicacao e util enquanto o vinculo real ainda nao foi definido
- apos contratacao do frete ou encerramento da venda, a indicacao perde prioridade operacional

## 13. Anexos do lote

O lote reaproveita a infraestrutura transversal de anexos da Etapa 3B.

Tipos esperados:
- e-mails
- inventarios
- fotos da carga
- fotos do sinistro
- NF
- comprovante de pagamento
- autorizacao de retirada
- comprovantes de despesas
- documentos do transporte
- documentos gerais do processo

Regras obrigatorias:
- anexos pertencem a entidade `lote`
- nao nasce infraestrutura nova para anexos nesta etapa
- a etapa reaproveita a infraestrutura existente

## 14. Cadastro inline

Contrato obrigatorio:
- qualquer cadastro necessario durante o fluxo do lote deve ser criado sem sair do modulo
- isso deve ocorrer por modal
- os cadastros continuam pertencendo ao modulo Cadastros
- o modulo Lotes nao cria cadastro paralelo

Escopo esperado:
- cliente
- motorista
- transportadora
- outro cadastro relacionado, se necessario

## 15. Registros entrelacados

O sistema deve permitir leitura cruzada entre as entidades relacionadas.

Obrigatoriedades conceituais:

Ao abrir um lote:
- ver fornecedor
- ver motorista e transportadora
- ver custos
- ver clientes compradores
- ver resultado do processo

Ao abrir um cliente:
- ver lotes comprados

Ao abrir um fornecedor:
- ver lotes comprados daquele vinculo

Ao abrir um motorista ou transportadora:
- ver lotes transportados

Regra estrutural:
- esses vinculos devem aparecer preferencialmente nas proprias fichas dos cadastros

## 16. Integracoes formais do modulo

### 16.1 Lotes -> Cadastros

Vinculos obrigatorios com cadastro central:
- `fornecedor_id`
- `cliente_id`
- `motorista_id`
- `transportadora_id`

Regra:
- nenhuma dessas entidades deve nascer em estrutura paralela dentro do modulo Lotes

### 16.2 Lotes -> Financeiro

Contrato esperado:
- venda do lote gera ordem de venda
- o Contas a Receber referencia a ordem de venda
- o CR recebe apenas os dados necessarios ao financeiro, sem assumir a logica de venda

### 16.3 Lotes -> Anexos

Contrato esperado:
- anexos do lote usam a infraestrutura transversal existente
- a entidade `lote` torna-se origem valida para relacao documental do processo

## 17. Enums oficiais da etapa

### 17.1 Status macro

- `em_transito`
- `em_estoque`
- `finalizado`

### 17.2 Tipo de controle do item

- `unidade`
- `kg`

### 17.3 Tipo de transporte

- `motorista_autonomo`
- `transportadora`
- `transporte_proprio`
- `sem_frete`
- `retirada_cliente`

### 17.4 Motivo de baixa

- `vendido`
- `site`
- `uso_interno`
- `avaria`
- `descartado`
- `outro`

Regra obrigatoria:
- esses valores nao sao livres
- eles devem nascer padronizados para garantir consistencia de banco, frontend, filtros e relatorios

## 18. Limites da Parte 4.1

Esta parte nao deve:
- criar migrations
- criar tabelas
- criar repositorios
- criar dashboard
- criar pagina interna
- criar formulario real
- criar CR
- criar anexo
- implementar modal de cadastro inline

Escopo da parte:
- consolidacao estrutural documental

## 19. Resultado esperado da parte

Ao final da Parte 4.1, o projeto deve possuir:
- contrato tecnico formal do modulo Lotes
- entidade `lote` definida
- blocos do processo consolidados
- regras de baixa, venda e resultado definidas
- enums oficiais fechados
- vinculos com Cadastros, Financeiro e Anexos formalizados
- base pronta para a persistencia real da Parte 4.2
