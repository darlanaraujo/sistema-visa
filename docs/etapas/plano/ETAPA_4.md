PROJETO: Sistema Visa
CLIENTE: Visa Remoções

ETAPA: 4 — MÓDULO LOTES

MOMENTO: Estrutura Técnica Consolidada da Etapa
STATUS: ETAPA DEFINIDA PARA EXECUÇÃO
EXECUÇÃO TÉCNICA: ❌ NÃO AUTORIZADA

ORIGEM: Desenvolvimento — AuraLabs
DESTINO: Implementação — Lucas

RESPONSÁVEL TÉCNICO
Felipe Andrade
Líder de Engenharia — AuraLabs

SUPERVISÃO
Darlan
Direção — AuraLabs

────────────────────────────────────────

VISÃO GERAL DA ETAPA

A ETAPA 4 tem como objetivo implementar o Módulo Lotes como núcleo operacional da empresa, responsável por controlar todo o ciclo de compra, retirada, transporte, recebimento, organização interna, venda parcial ou total e encerramento econômico dos processos de lote.

Este módulo não deve ser tratado como simples cadastro de mercadorias.

Ele é um módulo de processo.

Cada lote representa um processo real de compra vinculado a:

• fornecedor (seguradora)  
• número de processo / sinistro  
• local de armazenagem  
• custos operacionais  
• transporte  
• recebimento  
• itens internos do lote  
• vendas parciais ou totais  
• resultado financeiro do processo  

O módulo deve permitir visão operacional, financeira e documental do processo.

A etapa também resolve a principal dor estrutural da empresa:

• controlar lotes que podem ser vendidos inteiros, parcialmente ou desmembrados  
• controlar itens sem criar um estoque global clássico  
• manter vínculo entre custo do processo e resultado de venda  
• permitir baixa manual sem obrigar venda  
• permitir venda dentro do próprio lote  
• manter histórico e rastreabilidade completa do processo

────────────────────────────────────────

PRINCÍPIO CENTRAL DO MÓDULO

LOTE NÃO É ESTOQUE GLOBAL.

LOTE É PROCESSO COM ESTOQUE INTERNO PRÓPRIO.

Isso significa:

• o lote nasce como um processo comprado  
• dentro dele pode existir uma lista de itens  
• esses itens possuem controle interno de disponibilidade  
• esse controle não representa estoque global do sistema  
• a movimentação dos itens pertence ao lote  
• a venda pode acontecer dentro do lote  
• a baixa pode ocorrer com ou sem venda  
• o lote só encerra quando todo o conteúdo interno tiver sido resolvido

Essa decisão elimina a necessidade de um módulo de estoque clássico para resolver a dor operacional atual.

────────────────────────────────────────

REGRA DE PERSISTÊNCIA

Todos os dados do módulo Lotes devem ser persistidos em banco de dados.

É proibido o uso de LocalStorage, SessionStorage ou qualquer mecanismo de armazenamento local para dados de domínio.

Toda leitura e escrita deve ocorrer via backend e persistência real.

Essa regra é obrigatória para toda a Etapa 4.

────────────────────────────────────────

MOMENTO DE ENTRADA DO LOTE NO SISTEMA

Regra obrigatória:

O lote só entra no sistema após compra confirmada.

Não será controlada no sistema a etapa de oferta não aceita.

Ou seja:

• proposta recebida por e-mail → não entra  
• estudo interno da oferta → não entra  
• aceite da compra e lote efetivamente adquirido → entra no sistema

Isso mantém o módulo focado apenas nos processos reais da empresa.

────────────────────────────────────────

ORIGEM REAL DO PROCESSO

O processo normalmente começa com um e-mail enviado por uma seguradora.

Esse e-mail pode conter:

• seguradora  
• número do processo ou sinistro  
• descrição do lote  
• inventário  
• fotos  
• documentos complementares  
• valor original da mercadoria  
• valor depreciado, quando houver  
• local de retirada  
• despesas de armazenagem e outras despesas locais  

Após aceite da proposta, o lote passa a existir operacionalmente.

O sistema deve então registrar o processo completo.

────────────────────────────────────────

ENTIDADE PRINCIPAL DO MÓDULO — LOTE

Cada lote deve possuir uma entidade central com os seguintes grupos de dados.

────────────────

IDENTIFICAÇÃO DO PROCESSO

• fornecedor (seguradora)  
• número do processo / sinistro  
• título do lote  
• descrição resumida  
• descrição operacional detalhada, se necessário  
• tipo macro do lote (ex.: aço, eletrodomésticos, papelaria, carga mista etc.)  
• data de compra  
• status macro do lote  
• etapa da timeline  
• observações gerais  

Regra estrutural:

A seguradora é tratada como um fornecedor dentro do módulo de Cadastros.

Não existe entidade separada para seguradora.

O mesmo vínculo cobre a leitura de “seguradora”, “vendedor do lote” e “origem da compra”.

────────────────

VALORES DO PROCESSO

• valor original do lote  
• valor depreciado informado, se houver  
• valor pago pela compra  
• despesas no local  
• frete  
• valor de emissão documental de transporte, se houver  
• outros custos adicionais  
• custo total do processo

Regra obrigatória:

O custo total do processo deve ser sempre calculado pela soma dos custos registrados.

O lote deve permitir visão clara de:

• valor de compra  
• custos agregados  
• custo total acumulado  
• valor total vendido  
• saldo atual  
• lucro ou prejuízo parcial  
• lucro ou prejuízo final

────────────────

LOCAL DE ARMAZENAGEM

O local de armazenagem deve ser registrado dentro do lote.

Ele não deve nascer como cadastro global obrigatório.

Campos esperados:

• nome do local ou responsável  
• nome do contato  
• telefone  
• e-mail  
• endereço  
• cidade  
• estado  
• observações do local

Decisão estrutural:

Esse bloco é interno ao lote.

Pode se parecer com um cadastro, mas não deve forçar cadastro reutilizável do local.

────────────────

DADOS OPERACIONAIS DO FRETE

• tipo de transporte
  • motorista autônomo
  • transportadora
  • transporte próprio
  • sem frete
  • retirada pelo cliente

• motorista ou transportadora vinculada, quando aplicável  
• veículo utilizado, quando aplicável  
• agenciador, se houver  
• valor do frete  
• documento de transporte / manifesto / CTE, quando houver  
• data de contratação  
• data de agendamento  
• data de coleta  
• data de entrega  
• observações logísticas

Decisão estrutural:

Frete não nasce como módulo próprio nesta etapa.

Ele é registro operacional dentro do lote, mantendo vínculo com cadastro de motorista ou transportadora quando existir.

────────────────────────────────────────

TIMELINE DO LOTE

A página do lote deve possuir uma timeline no topo responsável por mostrar o andamento do processo.

Essa timeline não é apenas decorativa.

Ela representa o estado operacional do lote.

Estrutura recomendada de macro etapas:

• Compra confirmada  
• Liberação  
• Coleta  
• Transporte  
• Recebido  
• Venda  
• Encerrado

Cada etapa principal deve possuir checklist interno.

Exemplo:

LIBERAÇÃO
• contato no local  
• confirmação de comunicação ao armazenador  
• confirmação das despesas  
• prazo de retirada confirmado

COLETA
• frete contratado  
• veículo definido  
• agendamento confirmado  
• coleta realizada

A timeline deve funcionar assim:

• etapas concluídas → marcadas com check  
• etapa atual → destacada  
• próximas etapas → neutras  
• avanço da etapa → depende do checklist da etapa atual

Regra obrigatória:

Não permitir pular etapas livremente.

O avanço deve ser orientado pela conclusão dos pontos operacionais esperados.

────────────────────────────────────────

STATUS MACRO DO DASHBOARD

A timeline é o controle interno detalhado do lote.

O dashboard do módulo deve usar um status macro simplificado para organização visual.

Status macro esperados:

• Em trânsito  
• Em estoque  
• Finalizado

Esses status não substituem a timeline.

Eles servem para organizar o mural principal do módulo.

Mapeamento esperado:

• lote em etapas de liberação, coleta ou transporte → Em trânsito  
• lote recebido e ainda com itens ativos → Em estoque  
• lote com todos os itens resolvidos → Finalizado

Regra estrutural:

O status macro do dashboard deve ser derivado do estado real do processo, e não tratado como trilha paralela independente da timeline.

────────────────────────────────────────

MURAL PRINCIPAL DO MÓDULO

A tela principal do módulo Lotes não deve ser construída em formato de lista simples.

Ela deve funcionar como um mural de cards organizado por status macro.

Estrutura recomendada:

Colunas principais:

• Em trânsito  
• Em estoque  
• Finalizados

Dentro de cada coluna:

• cards de lote

Cada card deve mostrar no mínimo:

• fornecedor / seguradora  
• número do processo  
• título do lote  
• cidade / estado do lote  
• etapa atual resumida  
• custo total atual  
• valor vendido atual, quando houver  
• saldo / resultado parcial, quando houver  
• imagem de capa ou logo da seguradora, se existir  
• indicador visual de prioridade ou estágio

O dashboard deve possuir:

• busca por processo  
• busca por seguradora  
• filtro por estado  
• filtro por cidade  
• filtro por status  
• filtro por período  
• filtro por lote com venda parcial  
• filtro por lote sem frete  
• filtro por lote encerrado

Também deve exibir indicadores no topo, como:

• total investido em lotes abertos  
• total em trânsito  
• total em estoque  
• total vendido no período  
• saldo parcial dos lotes em aberto  
• quantidade de lotes por estágio

Esse dashboard é o centro visual do módulo.

Regra estrutural:

A primeira página do módulo Lotes é sempre o dashboard interno.

Cada lote possui sua própria página interna dentro do módulo.

────────────────────────────────────────

ITENS INTERNOS DO LOTE

O lote deve permitir dois níveis de registro.

────────────────

NÍVEL 1 — REGISTRO GERAL DO LOTE

O lote pode ter um campo geral para representar o conjunto principal.

Exemplo:

• Lote de eletrodomésticos  
• tipo: unidade  
• quantidade total de itens  
• valor do lote  
• valor por unidade de referência  
• valor de venda por unidade de referência  
• total projetado de venda

Esse nível funciona como visão macro.

────────────────

NÍVEL 2 — LISTA DE ITENS DO LOTE

O sistema deve permitir que o usuário cadastre itens individuais dentro do lote.

Cada item deve possuir:

• nome / descrição do item  
• tipo de controle
  • unidade
  • kg

• quantidade total  
• quantidade disponível  
• quantidade baixada  
• quantidade vendida  
• custo unitário de referência  
• custo total do item  
• valor de venda unitário sugerido  
• valor total de venda sugerido  
• tags do item  
• observações do item  
• status do item

Fluxo de cadastro:

O usuário preenche os campos do item e clica em adicionar.

Ao adicionar:

• item entra na lista do lote  
• campos do formulário do item são limpos  
• o usuário pode continuar adicionando itens até concluir a lista

A lista precisa possuir ações por item:

• editar  
• baixar

Decisão estrutural:

Os itens do lote formam um estoque interno do processo.

Eles não entram em estoque global do sistema.

Cada item controla sua própria disponibilidade dentro do lote.

────────────────────────────────────────

REGRA DE BAIXA DO ITEM

A baixa do item é manual e não abre automaticamente uma venda.

Ela tem função operacional de controle interno do lote.

Um item pode ser baixado porque:

• foi vendido  
• foi enviado para o site  
• foi reservado para uso interno  
• veio com avaria  
• foi descartado  
• teve outra destinação fora da venda formal do lote

Por isso, a baixa precisa registrar:

• item  
• quantidade baixada  
• motivo da baixa  
• observação  
• data  
• responsável, se aplicável

Se o item for por unidade:

• baixa pode ser parcial ou total

Se o item for por kg:

• baixa pode registrar peso parcial

Quando a quantidade disponível chegar a zero:

• o item é considerado encerrado

Quando todos os itens do lote chegarem a zero:

• o lote pode ser considerado finalizado

Também deve existir:

• botão de baixa total do item  
• botão de baixa total do lote, quando aplicável, desde que opere sobre os itens

Regra importante:

A baixa não exige venda.

A baixa existe para resolver o estado operacional do item.

────────────────────────────────────────

VENDA DENTRO DO LOTE

A venda deve acontecer dentro da própria página do lote.

Essa é a abordagem correta para este módulo.

Motivos:

• a venda depende do lote  
• o lote precisa controlar custo x venda x resultado  
• o lote pode ser vendido parcial ou totalmente  
• a venda precisa dar baixa nos itens do próprio lote  
• isso evita modularização excessiva e retrabalho

Fluxo da venda:

Haverá um botão “Vender”.

Ao clicar:

• abre modal de venda

Dentro do modal:

1. definir comprador  
• selecionar cliente existente  
• permitir cadastro inline do cliente, se necessário

2. selecionar item do lote  
• lista apenas itens ainda disponíveis  
• ao selecionar item, sistema mostra:
  • nome do item
  • quantidade disponível
  • custo unitário
  • valor de venda sugerido

3. definir quantidade da venda  
• unidade ou kg, conforme o item

4. definir valor de venda  
• campo livre  
• pode ser diferente do sugerido

5. sistema calcula o total

6. botão adicionar item da venda  
• permite montar venda com mais de um item

7. definir forma de pagamento  
• usar modos definidos em Ferramentas

8. finalizar venda

Ao finalizar:

• gera registro da venda do lote  
• dá baixa automática na quantidade vendida do item  
• se zerar a disponibilidade, o item encerra  
• se ainda restar saldo, item continua disponível  
• gera CR no Financeiro com:
  • cliente
  • forma de pagamento
  • valor

Decisão importante:

Venda não substitui o controle do item.

Venda é uma movimentação comercial que afeta o item.

Regra estrutural complementar:

Cada venda deve gerar uma ordem de venda do lote.

O Contas a Receber deve referenciar essa ordem.

Essa ordem deve permitir abertura de recibo ou comprovante interno da venda dentro do sistema.

────────────────────────────────────────

DIFERENÇA ENTRE BAIXA E VENDA

Regra central do módulo:

BAIXA ≠ VENDA

Baixa:
• controle operacional de saída do item do lote  
• pode ou não gerar receita

Venda:
• gera receita  
• gera CR  
• baixa quantidade correspondente do item

Essa separação é obrigatória para o módulo funcionar corretamente.

────────────────────────────────────────

LUCRO E PREJUÍZO DO LOTE

O módulo deve calcular o resultado do lote em tempo real.

Fórmula esperada:

lucro_prejuizo_parcial = total_vendido - custo_total

Esse valor deve ser atualizado sempre que houver:

• nova despesa  
• novo frete  
• novo documento de transporte  
• nova venda  
• nova baixa sem venda que reduza o conteúdo aproveitável do lote

O sistema deve exibir:

• custo total atual  
• total vendido atual  
• saldo parcial  
• lucro ou prejuízo parcial  
• lucro ou prejuízo final quando o lote estiver encerrado

────────────────────────────────────────

REGRA FINANCEIRA DO MÓDULO

O módulo Lotes não realiza controle contábil por item.

As regras são:

• o custo é sempre tratado no nível do lote  
• não existe rateio obrigatório de custo por item  
• o sistema não recalcula custo unitário real por item  
• itens possuem apenas valores de referência  

Baixa sem venda:

• não gera receita  
• não dispara recálculo automático de custo  
• impacta apenas o contexto operacional do lote

Resultado financeiro:

• baseado exclusivamente em:
  • total_vendido
  • custo_total

Essa simplificação é intencional e obrigatória para o funcionamento do módulo.

────────────────────────────────────────

MOVIMENTAÇÕES DO LOTE

Além da timeline, o lote deve possuir um histórico de movimentações.

Esse histórico deve registrar:

• adição de custo  
• mudança de etapa  
• contratação de frete  
• baixa de item  
• venda  
• anexo importante  
• alteração relevante do processo

Isso permite:

• rastreabilidade  
• auditoria interna  
• consulta histórica  
• relatórios futuros

Direção estrutural:

As movimentações devem nascer como histórico oficial do lote.

Esse histórico deve ser cronológico e orientado por eventos reais do processo.

────────────────────────────────────────

CRUZAMENTOS INTELIGENTES

O módulo deve usar cruzamento de tags para gerar indicações.

As tags devem ser tratadas como estrutura global reutilizável do sistema, permitindo cruzamentos entre cadastros, lotes e itens.

────────────────

INDICAÇÃO DE MOTORISTAS

O local do lote deve cruzar com:

• motoristas  
• transportadoras  
• tags de rota

O sistema deve sugerir motoristas / transportadoras com compatibilidade de rota.

Essa lista de sugestão é útil até a contratação do frete.

Após o frete ser definido:

• a indicação perde prioridade operacional  
• o lote passa a exibir o motorista efetivamente contratado

────────────────

INDICAÇÃO DE CLIENTES

As tags dos itens ou do lote devem cruzar com:

• tags de interesse do cadastro do cliente

O sistema deve sugerir possíveis clientes compradores para o lote ou para seus itens.

Essa indicação faz sentido enquanto ainda existir conteúdo disponível no lote.

Quando o lote for encerrado:

• a indicação deixa de ter importância operacional

────────────────────────────────────────

ANEXOS DO LOTE

O lote deve usar a infraestrutura de anexos já criada na ETAPA 3B.

Tipos comuns esperados:

• e-mails  
• inventários  
• fotos da carga  
• fotos do sinistro  
• NF  
• comprovante de pagamento do lote  
• autorização de retirada  
• comprovantes de despesas  
• documentos do transporte  
• documentos adicionais do processo

Esses anexos pertencem ao processo do lote.

Devem ser visíveis na ficha e no controle operacional do lote.

────────────────────────────────────────

CADASTRO INLINE OBRIGATÓRIO

O módulo Lotes deve resolver a dor já identificada no Financeiro.

Se durante o fluxo for necessário criar:

• cliente  
• motorista  
• transportadora  
• outro cadastro necessário

o sistema deve permitir isso sem sair do lote.

Direção obrigatória:

• abertura por modal  
• cadastro inline  
• retorno ao fluxo original sem perda de contexto

Essa é uma regra estrutural do sistema daqui para frente.

Todos os cadastros continuam pertencendo ao módulo Cadastros.

O lote não cria estrutura paralela de cadastro.

────────────────────────────────────────

REGISTROS ENTRELAÇADOS

O sistema deve permitir leitura cruzada entre os módulos.

Exemplos obrigatórios:

Ao abrir um lote:
• ver de quem comprou  
• quem transportou  
• quais custos teve  
• para quem vendeu  
• quanto rendeu  
• quais clientes foram apenas indicados  
• qual motorista foi apenas sugerido  
• qual motorista foi contratado  

Ao abrir um cliente:
• ver o que já comprou  
• acessar os lotes relacionados  

Ao abrir um fornecedor / vendedor do lote:
• ver os lotes comprados daquele vínculo, quando aplicável  

Ao abrir um motorista ou transportadora:
• ver quais lotes transportou  
• acessar diretamente os processos

Essas relações devem aparecer preferencialmente nas próprias fichas de cadastro.

Não em uma área isolada distante do contexto.

────────────────────────────────────────

RELATÓRIOS

Os relatórios do módulo são centrais.

Dois níveis obrigatórios:

────────────────

RELATÓRIOS GERAIS

• lotes do período  
• lotes em trânsito  
• lotes em estoque  
• lotes finalizados  
• lotes com prejuízo  
• lotes com lucro  
• lotes por seguradora  
• lotes por estado  
• lotes por motorista  
• lotes por cliente  

────────────────

RELATÓRIO INDIVIDUAL DO PROCESSO

Cada lote deve ser imprimível como ficha do processo.

Essa ficha deve mostrar:

• identificação do lote  
• fornecedor / seguradora  
• processo  
• local  
• custos  
• frete  
• timeline  
• itens  
• vendas  
• anexos  
• resultado final ou parcial

────────────────────────────────────────

DECISÃO SOBRE MÓDULO DE VENDAS

Fica consolidado o seguinte:

• vendas de itens de lote acontecem dentro do lote  
• módulo de vendas externo permanece para vendas livres, sem vínculo com lote  
• nesse módulo externo o produto pode ser digitado livremente  
• o único vínculo obrigatório é o cliente  
• ao finalizar, o sistema gera CR no Financeiro

Separação correta:

• venda operacional do lote → dentro do lote  
• venda avulsa fora de lote → módulo de vendas externo

────────────────────────────────────────

ENUMS E PADRONIZAÇÕES DO MÓDULO

Os seguintes valores devem ser tratados como enums controlados:

STATUS MACRO:
• em_transito  
• em_estoque  
• finalizado  

TIPO DE CONTROLE DO ITEM:
• unidade  
• kg  

TIPO DE TRANSPORTE:
• motorista_autonomo  
• transportadora  
• transporte_proprio  
• sem_frete  
• retirada_cliente  

MOTIVO DE BAIXA:
• vendido  
• site  
• uso_interno  
• avaria  
• descartado  
• outro  

Regra obrigatória:

Esses valores não devem ser livres no sistema.

Devem ser padronizados para garantir consistência de dados, filtros, dashboard, relatórios e integrações internas do módulo.

────────────────────────────────────────

DECISÕES ESTRUTURAIS CONSOLIDADAS DA ETAPA 4

Ficam formalmente consolidadas as seguintes decisões:

• Lote é processo, não estoque global  
• Todo dado do módulo Lotes é persistido em banco  
• Lote só entra após compra confirmada  
• Fornecedor / seguradora é sempre vínculo do cadastro central  
• Local de armazenagem é interno ao lote  
• Frete é registro dentro do lote com vínculo opcional a cadastro  
• Timeline controla o processo interno  
• Dashboard organiza por status macro  
• Itens do lote funcionam como estoque interno do processo  
• Cada item pode ser controlado por unidade ou kg  
• Baixa é manual e não depende de venda  
• Venda acontece dentro do lote  
• Venda gera ordem de venda e CR  
• O módulo trabalha com custo global do lote, não custo contábil por item  
• Lote calcula lucro ou prejuízo parcial em tempo real  
• Lote encerra quando todos os itens forem resolvidos  
• Tags são globais e usadas para cruzamentos  
• Cadastro inline é obrigatório  
• Registros entrelaçados devem existir entre lote, cliente, motorista e demais entidades relacionadas

────────────────────────────────────────

DIVISÃO TÉCNICA DA ETAPA 4

ETAPA 4.1  
Modelagem estrutural do módulo Lotes

O que acontece nesta parte:

• definição formal da entidade lote  
• definição da timeline  
• definição dos status macro do dashboard  
• definição dos custos do processo  
• definição do local de armazenagem interno  
• definição das relações com fornecedor / seguradora, motorista, transportadora e cliente  
• definição do histórico de movimentações  
• definição formal dos enums do módulo  
• definição da regra de persistência da etapa

Objetivo:

fechar a arquitetura do módulo antes de implementar persistência e interface.

Resultado esperado:

• documento estrutural completo do módulo  
• regras consolidadas sem ambiguidade  
• base pronta para modelagem de persistência

Impacto técnico: 🔴 Alto

────────────────

ETAPA 4.2  
Persistência do lote e das estruturas centrais do processo

O que acontece nesta parte:

• criação da base de dados do lote  
• criação da timeline / status operacional  
• criação da estrutura de custos  
• criação da estrutura de local de armazenagem interno  
• criação da estrutura de movimentações do lote  
• criação das relações com entidades já existentes  
• persistência totalmente em banco, sem uso de armazenamento local

Objetivo:

implantar a base real do processo de lote.

Resultado esperado:

• persistência real do núcleo do lote  
• base pronta para dashboard e ficha do processo

Impacto técnico: 🔴 Alto

────────────────

ETAPA 4.3  
Dashboard principal do módulo Lotes

O que acontece nesta parte:

• criação do mural principal do módulo  
• colunas por status macro  
• cards de lote  
• métricas superiores  
• busca e filtros iniciais

Objetivo:

entregar a visão operacional central da empresa.

Resultado esperado:

• dashboard visual forte  
• leitura rápida dos processos  
• organização por estágio do lote

Impacto técnico: 🟡 Médio / Alto

────────────────

ETAPA 4.4  
Página interna do lote — estrutura base

O que acontece nesta parte:

• criação da ficha principal do lote  
• bloco de identificação  
• bloco de custos  
• bloco de local de armazenagem  
• bloco de frete  
• timeline do processo  
• bloco inicial de movimentações

Objetivo:

entregar a página viva do processo.

Resultado esperado:

• lote consultável em profundidade  
• base pronta para itens, vendas e anexos

Impacto técnico: 🔴 Alto

────────────────

ETAPA 4.5  
Itens do lote e controle interno de disponibilidade

O que acontece nesta parte:

• criação do formulário de item  
• lista interna de itens  
• suporte a unidade e kg  
• cálculo de custo e valor sugerido  
• ações de editar e baixar  
• baixa manual com motivo obrigatório  
• baixa total opcional

Objetivo:

transformar o lote em estoque interno do processo.

Resultado esperado:

• itens controlados dentro do lote  
• disponibilidade real por item  
• base pronta para vendas parciais

Impacto técnico: 🔴 Alto

────────────────

ETAPA 4.6  
Venda dentro do lote

O que acontece nesta parte:

• modal de venda  
• seleção ou cadastro inline de cliente  
• seleção de itens disponíveis  
• venda parcial ou total  
• forma de pagamento  
• baixa automática por venda  
• criação da ordem de venda  
• geração de CR no Financeiro referenciando a ordem

Objetivo:

permitir venda operacional do lote sem depender de módulo externo.

Resultado esperado:

• venda parcial ou total dentro do lote  
• ordem de venda registrada  
• CR gerada corretamente  
• impacto automático na disponibilidade dos itens

Impacto técnico: 🔴 Alto

────────────────

ETAPA 4.7  
Cruzamentos inteligentes por tags

O que acontece nesta parte:

• cruzamento entre local / rotas e motoristas  
• cruzamento entre itens / tags e clientes  
• lista de indicações operacionais  
• ocultação natural da importância da indicação após definição do vínculo real

Objetivo:

gerar inteligência operacional do processo.

Resultado esperado:

• sugestões de motoristas  
• sugestões de clientes  
• apoio real à operação

Impacto técnico: 🟡 Médio

────────────────

ETAPA 4.8  
Anexos do lote

O que acontece nesta parte:

• integração do lote com a infraestrutura transversal de anexos  
• upload  
• visualização  
• organização de documentos do processo

Objetivo:

centralizar toda documentação do lote no próprio processo.

Resultado esperado:

• ficha documental completa do lote  
• rastreabilidade documental do processo

Impacto técnico: 🟡 Médio

────────────────

ETAPA 4.9  
Cadastro inline dentro do fluxo do lote

O que acontece nesta parte:

• criação de cliente, motorista ou transportadora por modal  
• retorno seguro ao fluxo do lote  
• atualização imediata do contexto após cadastro

Objetivo:

eliminar quebra de fluxo operacional.

Resultado esperado:

• continuidade operacional sem saída do módulo  
• padrão sistêmico consolidado

Impacto técnico: 🟡 Médio / Alto

────────────────

ETAPA 4.10  
Registros entrelaçados e vínculos entre entidades

O que acontece nesta parte:

• mostrar histórico de compras, fretes e vendas nas fichas relacionadas  
• permitir navegação cruzada entre lote, cliente, motorista, transportadora e demais vínculos

Objetivo:

transformar o sistema em rede de contexto, não em módulos isolados.

Resultado esperado:

• leitura cruzada de histórico  
• visão contextual rica em cada ficha

Impacto técnico: 🟡 Médio / Alto

────────────────

ETAPA 4.11  
Relatórios do módulo Lotes

O que acontece nesta parte:

• relatórios gerais  
• relatórios por período  
• ficha imprimível do lote  
• visão de lucro/prejuízo por processo  
• relatórios por seguradora, cliente, motorista, estágio e resultado

Objetivo:

entregar capacidade real de consulta e tomada de decisão.

Resultado esperado:

• módulo pronto para operação e análise  
• ficha do lote imprimível  
• relatórios gerenciais úteis

Impacto técnico: 🟡 Médio / Alto

────────────────

ETAPA 4.12  
Validação funcional da etapa

O que acontece nesta parte:

• validação ponta a ponta do módulo  
• criação de lote  
• timeline  
• custos  
• itens  
• baixa  
• venda  
• ordem de venda  
• CR  
• anexos  
• cruzamentos  
• relatórios

Objetivo:

confirmar que toda a etapa funciona de forma integrada.

Resultado esperado:

• etapa funcionalmente estável  
• falhas identificadas com clareza, se existirem

Impacto técnico: 🟡 Médio

────────────────

ETAPA 4.13  
Auditoria técnica da etapa

O que acontece nesta parte:

• auditoria estrutural  
• auditoria de reaproveitamento  
• auditoria de UX/UI  
• auditoria de consistência entre lote, financeiro, cadastros e anexos  
• auditoria final da arquitetura da etapa

Objetivo:

encerrar a etapa com integridade técnica e aderência ao padrão AuraLabs.

Resultado esperado:

• etapa auditada  
• pronta para encerramento formal  
ou  
• ajustes finais claramente apontados

Impacto técnico: 🟡 Médio

────────────────────────────────────────

RESULTADO FINAL ESPERADO DA ETAPA 4

Ao final da ETAPA 4, o Sistema Visa deverá possuir:

• módulo Lotes funcional  
• controle completo do processo de compra de lote  
• timeline operacional do processo  
• dashboard principal por estágios  
• controle interno de itens por unidade ou kg  
• baixa manual com motivo  
• venda parcial ou total dentro do lote  
• geração de ordem de venda e CR a partir da venda  
• cálculo de custo total e lucro/prejuízo parcial ou final  
• cruzamento inteligente de motoristas e clientes  
• anexos completos do processo  
• cadastro inline sem quebra de fluxo  
• histórico entrelaçado entre entidades  
• relatórios operacionais e gerenciais do processo

────────────────────────────────────────

CONCLUSÃO

A ETAPA 4 passa a ser a etapa central do Sistema Visa.

Ela consolida o processo real da empresa dentro do sistema, com controle operacional, financeiro, documental e relacional.

Essa etapa deve ser tratada com prioridade máxima de clareza, consistência e profundidade, porque o restante da operação gira em torno dela.

────────────────────────────────────────

Felipe Andrade
Líder de Engenharia — AuraLabs

Darlan
Direção — AuraLabs