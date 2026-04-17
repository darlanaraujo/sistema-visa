# Sistema Visa - Etapa 4

## Status

Concluida, validada e auditada.

## Objetivo da etapa

Consolidar o modulo `Lotes` como nucleo operacional de processos comprados da empresa, permitindo acompanhar compra, movimentacao, frete, itens internos, venda, ocorrencias excepcionais, exposicao publica controlada e leitura economica do processo dentro da base real do projeto.

## Entregas consolidadas

- consolidacao da entidade `lotes` como processo operacional, e nao como estoque global classico
- implementacao da ficha completa do lote com leitura operacional, financeira, documental e historica
- consolidacao da timeline operacional com registros por etapa e historico de movimentacoes
- implementacao do controle interno de itens do lote com disponibilidade, baixa, venda e devolucao
- consolidacao da leitura economica do processo com custo total, vendido, devolvido e saldo do lote
- implementacao da separacao entre compromisso de compra e pagamento da compra, com status financeiro proprio
- consolidacao dos estados de cancelamento com historico preservado e leitura de estorno
- implementacao das visoes dedicadas de `ativos`, `estoque`, `finalizados` e `cancelados` dentro do dashboard do modulo
- consolidacao da infraestrutura de anexos do lote por contexto operacional
- implementacao da ficha publica do lote com publicacao controlada por token e galeria comercial
- criacao das versoes publicas de impressao da lista e da ficha do lote
- integracao transversal com `Cadastros` para compras, vendas e fretes relacionados ao lote
- consolidacao do painel analitico do modulo com relatorios visuais e impressao de recortes

## Arquivos e areas-chave da etapa

- `database/migrations/018_create_lotes_table.sql`
- `database/migrations/019_create_lote_itens_table.sql`
- `database/migrations/020_create_lote_movimentacoes_table.sql`
- `database/migrations/021_create_lote_item_tag_rel_table.sql`
- `database/migrations/022_create_lote_tag_rel_table.sql`
- `database/migrations/023_alter_lote_itens_tipo_controle_add_metros.sql`
- `public_php/src/Repositories/LoteRepository.php`
- `app/modules/lotes/home.php`
- `app/modules/lotes/_payment_helpers.php`
- `app/modules/lotes/_public_helpers.php`
- `app/templates/lote_publico.php`
- `app/templates/lote_publico_content.php`
- `app/templates/lote_publico_ficha_print.php`
- `app/templates/lote_publico_ficha_print_content.php`
- `app/templates/lote_publico_lista_print.php`
- `app/templates/arquivo_publico.php`
- `app/static/js/lotes/lotes_dashboard.js`
- `app/static/js/lotes_publico.js`
- `app/static/css/lotes.css`
- `app/static/css/lotes_publico.css`
- `app/modules/cadastros/_lotes_relacionados.php`
- `app/modules/cadastros/ficha.php`
- `app/static/js/cadastros/cadastros_listagem.js`

## Estrutura consolidada

Ao final da etapa, o modulo `Lotes` ficou consolidado sobre estes principios:

- lote como processo comprado com ciclo proprio
- itens internos pertencendo ao lote, sem criar estoque global classico
- movimentacoes e timeline como trilha oficial de rastreabilidade
- custo economico separado da quitacao financeira da compra
- publicacao externa controlada sem expor a ficha interna
- documentos e imagens organizados por contexto operacional
- reaproveitamento de `Cadastros` como base oficial para fornecedor, cliente, motorista e transportadora

Estados consolidados na etapa:

- `em_transito`
- `em_estoque`
- `finalizado`
- `cancelado`

Leituras complementares consolidadas:

- pagamento da compra com status `pendente` ou `pago`
- cancelamento com estados `cancelado_sem_pagamento`, `cancelado_aguardando_estorno` e `cancelado_estornado`
- visoes dedicadas de estoque, finalizados e cancelados dentro do proprio modulo

## Integracoes consolidadas

### Cadastros

O modulo passou a operar com integracao real com `Cadastros`, usando a base oficial para:

- fornecedor vinculado a compra do lote
- cliente vinculado as vendas dos itens do lote
- motorista e transportadora vinculados ao frete
- leitura cruzada nas fichas de cadastro em `Compras em lotes`, `Vendas em lotes` e `Fretes em lotes`

### Anexos

A infraestrutura transversal de anexos foi reaproveitada no modulo com separacao por contexto:

- anexos do processo
- anexos dos produtos
- anexos do frete
- anexos do cancelamento, quando aplicavel

### Fluxo publico

O modulo passou a contar com exposicao publica controlada para fins comerciais, com:

- ativacao e desativacao da ficha publica por lote
- token proprio de acesso
- lista publica de itens disponiveis
- ficha publica com galeria de imagens
- impressao publica da lista e da ficha
- acesso controlado a imagens publicadas via `arquivo_publico.php`

## Resultado consolidado

Ao final da Etapa 4, o sistema passou a contar com:

- modulo `Lotes` funcional de ponta a ponta
- controle operacional do processo de compra e movimentacao do lote
- leitura financeira e economica do processo dentro da ficha do lote
- controle de itens internos com venda, devolucao e baixa
- tratamento formal de cancelamento com historico preservado
- dashboard com recortes operacionais e analiticos do modulo
- exposicao publica controlada do lote para uso comercial
- integracao transversal do modulo com `Cadastros` e anexos

## Validacao consolidada

O fechamento da etapa foi sustentado por:

- leitura funcional do modulo pelo estado real do projeto, conforme registrado em `docs/etapas/ETAPA_4_REALINHAMENTO.md`
- validacao estrutural da ficha do lote, timeline, itens, vendas, cancelamentos, painel analitico e fluxo publico
- confirmacao da integracao real com `Cadastros` nas leituras relacionadas do modulo
- validacao de sintaxe com `/Applications/XAMPP/xamppfiles/bin/php -l` em `home.php`, helpers e templates publicos do modulo
- validacao de sintaxe com `node -c` em `app/static/js/lotes/lotes_dashboard.js`, `app/static/js/lotes_publico.js` e `app/static/js/cadastros/cadastros_listagem.js`
- verificacao de persistencia real na base atual, com leitura confirmada de lotes, itens e movimentacoes existentes
- verificacao de que o modulo nao usa `localStorage` ou `sessionStorage` para persistencia de dominio, mantendo apenas uso transitivo de `sessionStorage` para flash e rolagem do dashboard

Validacoes objetivas executadas durante o fechamento:

- base atual respondendo com `2` lotes, `58` movimentacoes e `1` item persistidos
- base atual com cenarios de `em_transito` e `cancelado`, cobrindo operacao ativa e encerramento excepcional
- ficha publica persistida e publicada em pelo menos um lote da base atual

## Auditoria consolidada

A auditoria tecnica da etapa, feita sobre o projeto como ele esta hoje, confirmou:

- aderencia estrutural do modulo ao uso de repositorio e persistencia real
- reaproveitamento correto da base de `Cadastros` e da infraestrutura de anexos
- separacao funcional entre ficha interna, ficha publica e helpers de apoio
- consistencia entre estados macro, movimentacoes, cancelamento e leitura analitica
- ausencia de modulo paralelo para estoque classico, preservando a diretriz de lote como processo
- ausencia de bloqueios tecnicos visiveis na leitura estatica e nas validacoes executadas

Risco residual identificado:

- a validacao final aqui registrada foi tecnica e estrutural, com apoio em sintaxe e leitura da base real; uma rodada visual manual em navegador continua sendo desejavel sempre que houver nova passada de refinamento cosmetico no modulo

## Diferencas em relacao ao documento de origem

Durante a execucao da etapa, o projeto recebeu refinamentos e reorganizacoes que nao ficaram integralmente refletidos no plano original.

As diferencas relevantes consolidadas no fechamento sao:

- o projeto real passou a prevalecer como fonte principal de fechamento da etapa
- o modulo consolidou ficha publica, impressao publica e controle de publicacao por token como parte efetiva da entrega
- o fechamento do dashboard foi reorganizado com visoes dedicadas de `estoque`, `finalizados` e `cancelados`
- a leitura de pagamento da compra foi formalizada de maneira leve, separando compromisso economico e quitacao financeira
- a auditoria e a validacao passaram a ser feitas sobre o repositorio consolidado, e nao apenas sobre o desenho original de plano

Essas diferencas foram formalmente enquadradas antes do fechamento em:

- `docs/etapas/ETAPA_4_REALINHAMENTO.md`

## Observacao

Este documento consolida retrospectivamente a Etapa 4 com base no estado atual do repositorio e na execucao real do projeto.

Os documentos em `docs/etapas/plano` permanecem uteis como memoria de origem e intencao estrutural, mas o encerramento tecnico da etapa passa a refletir o que foi efetivamente implementado, validado e auditado no modulo `Lotes`.
