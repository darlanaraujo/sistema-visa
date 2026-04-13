<proposed_plan>
## Fechamento do Módulo Lotes

### Resumo
Fechar o módulo com 4 frentes finais: refino do `Relatório do lote`, criação das páginas dedicadas de `Lotes em estoque`, `Lotes finalizados` e `Lotes cancelados`, consolidação dos estados de cancelamento, e registros entrelaçados nas fichas de `Cadastros`. A visão agregada `Encerrados` deixa de ser prioridade; a navegação passa a ser separada entre `Finalizados` e `Cancelados`. Também entra nessa passada o refinamento visual dos 3 botões do dashboard ligados a essas visões.

### Mudanças principais
- **Relatório do lote**
  - Reestruturar o relatório individual para virar a foto completa do lote no momento da geração.
  - Garantir exibição correta do `título do lote`.
  - Trocar a seção de valores espalhados por uma seção única `Custos do lote`, organizada em blocos:
    - compra do lote
    - custos de armazenagem/local
    - custos de frete
    - outros custos
    - total
  - Criar seção `Resultado econômico` com:
    - custo total
    - total vendido
    - devoluções/ocorrências financeiras, se houver
    - lucro/prejuízo
  - Enriquecer a seção `Frete` com dados do motorista/transportadora.
  - Adicionar seções detalhadas:
    - itens do lote
    - vendas do lote
    - ocorrências excepcionais
  - Exibir explicitamente `data e hora de geração` no relatório.
  - Manter o padrão visual já aprovado dos relatórios do sistema.

- **Dashboard e páginas de navegação dos lotes**
  - O mural principal do dashboard passa a mostrar apenas:
    - `Em trânsito`
    - `Em estoque`
  - O card `Em estoque` do dashboard continua exibindo no máximo 9 processos e leva para uma página dedicada com a lista completa.
  - Criar páginas dedicadas para:
    - `Lotes em estoque`
    - `Lotes finalizados`
    - `Lotes cancelados`
  - Essas páginas devem reutilizar a mesma linguagem do dashboard:
    - busca
    - filtros
    - KPIs específicos da visão
    - coluna de widgets
    - cards de lote no mesmo padrão visual
  - Quando não houver dados, mostrar estado vazio com ícone/ilustração e texto claro.
  - Ordenação esperada:
    - `Em estoque`: por data de entrega
    - `Finalizados`: por data de finalização
    - `Cancelados`: por status de cancelamento e data da ocorrência
  - Melhorar visualmente os 3 botões/atalhos do dashboard ligados a essas visões, deixando-os menos pobres e mais consistentes com o restante do módulo.

- **Lotes cancelados**
  - Formalizar estados de cancelamento:
    - `cancelado_sem_pagamento`
    - `cancelado_aguardando_estorno`
    - `cancelado_estornado`
  - Atualizar a leitura da ficha, dashboard e página de cancelados para usar esses estados.
  - A página `Lotes cancelados` deve mostrar KPIs coerentes com esse fluxo, por exemplo:
    - total cancelado
    - aguardando estorno
    - estornados
    - valor potencial/devolvido quando aplicável
  - O lote cancelado continua preservando histórico, documentos e ocorrências.

- **Registros entrelaçados nas fichas relacionadas**
  - Nas fichas de `Cadastros`, criar seções em formato tabela/lista para relacionamento com lotes.
  - Seções mínimas:
    - `Compras em lotes`
    - `Vendas em lotes`
    - `Fretes em lotes`
  - Regras:
    - qualquer cadastro que tenha sido fornecedor/origem de compra aparece em `Compras em lotes`
    - qualquer cadastro que tenha comprado item de lote aparece em `Vendas em lotes`
    - motorista/transportadora aparece em `Fretes em lotes`
  - Cada linha deve trazer poucos dados e um link direto para o lote, no mesmo espírito do Financeiro:
    - processo
    - título do lote
    - data
    - valor relacionado quando fizer sentido
    - status
    - link para abrir a página do lote

### Implementação esperada
- Reaproveitar a estrutura atual do dashboard de `Lotes`, sem criar páginas paralelas com linguagem visual nova.
- Reaproveitar os helpers e o template de impressão já existentes para o `Relatório do lote`.
- Reaproveitar na ficha de `Cadastros` o mesmo padrão de tabela enxuta já usado em listagens relacionadas do sistema.
- Atualizar a navegação/atalhos do módulo para apontar para:
  - estoque
  - finalizados
  - cancelados
- Remover a dependência conceitual da visão agregada `Encerrados` como destino principal.

### Testes e cenários
- Gerar `Relatório do lote` e validar:
  - título correto
  - seção única de custos
  - resultado econômico
  - frete detalhado
  - itens e vendas detalhados
  - data/hora de geração
- No dashboard:
  - confirmar que o mural principal mostra só `Em trânsito` e `Em estoque`
  - validar os atalhos novos de estoque/finalizados/cancelados
- Nas páginas dedicadas:
  - validar estado vazio
  - validar busca/filtros/KPIs/widgets
  - validar ordenação esperada
- Em cancelados:
  - validar classificação por estado de cancelamento
  - validar leitura dos lotes cancelados com e sem estorno
- Em `Cadastros`:
  - abrir ficha de fornecedor com compras em lote
  - abrir ficha de cliente com vendas em lote
  - abrir ficha de motorista/transportadora com fretes em lote
  - validar link direto para a página do lote

### Assumptions
- A visão `Encerrados` deixa de ser prioridade e será substituída na prática pela separação entre `Finalizados` e `Cancelados`.
- Os relatórios gerais adicionais do módulo não entram agora; o painel analítico atual já atende a demanda básica.
- O entrelaçamento nas fichas deve mostrar listas objetivas e navegáveis, não dashboards internos completos.
</proposed_plan>