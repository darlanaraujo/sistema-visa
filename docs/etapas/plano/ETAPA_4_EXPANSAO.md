────────────────────────────────────────

EXPANSÃO ESTRUTURAL — CANCELAMENTO DE LOTE E SEGMENTAÇÃO DE ENCERRAMENTO

Esta expansão introduz duas novas partes na ETAPA 4 com o objetivo de:

• tratar corretamente o cancelamento de lotes como evento operacional real  
• preservar integridade financeira e rastreabilidade  
• evitar poluição do dashboard principal  
• separar operação ativa de histórico encerrado  

Esta expansão NÃO altera o fluxo atual das partes já em execução.

Ela deve ser considerada como evolução estrutural a partir da conclusão das partes intermediárias.

────────────────────────────────────────

NOVA PARTE 4.11  
Cancelamento de Lote e Estados de Encerramento

O que acontece nesta parte:

• criação da ação de cancelamento de lote  
• obrigatoriedade de justificativa de cancelamento  
• verificação de existência de pagamento vinculado ao lote  
• definição de fluxo de cancelamento com ou sem impacto financeiro  
• criação de estados específicos de cancelamento  
• registro do cancelamento como movimentação oficial do lote  
• atualização da timeline com evento de cancelamento  
• definição do encerramento do lote via cancelamento  

Objetivo:

permitir que o sistema trate corretamente cenários reais onde o lote não se concretiza operacionalmente, mantendo consistência financeira e histórico completo do processo.

Resultado esperado:

• lote pode ser cancelado de forma controlada  
• cancelamento exige motivo registrado  
• sistema diferencia cancelamento com e sem pagamento  
• sistema controla estado de estorno quando necessário  
• histórico do lote permanece íntegro  
• timeline reflete o cancelamento como evento do processo  

Impacto técnico: 🔴 Alto

────────────────

REGRAS ESTRUTURAIS DO CANCELAMENTO

Cancelamento não é exclusão.

O lote cancelado deve permanecer no sistema com todos os seus dados, mantendo:

• vínculo com fornecedor  
• valores do processo  
• histórico de movimentações  
• registros financeiros  
• rastreabilidade completa  

────────────────

FLUXO DE CANCELAMENTO

CENÁRIO 1 — LOTE SEM PAGAMENTO

• usuário aciona cancelamento  
• sistema exige motivo  
• lote é encerrado imediatamente  
• não há pendência financeira  

Resultado:

• status_macro passa a finalizado  
• status_cancelamento = cancelado_sem_pagamento  

────────────────

CENÁRIO 2 — LOTE COM PAGAMENTO

• usuário aciona cancelamento  
• sistema exige motivo  
• sistema identifica pagamento realizado  
• lote entra em estado intermediário de estorno  

Resultado inicial:

• status_macro passa a finalizado  
• status_cancelamento = cancelado_aguardando_estorno  

Após confirmação do estorno:

• status_cancelamento = cancelado_estornado  

Regra obrigatória:

O lote só é considerado completamente resolvido após confirmação do estorno.

────────────────

ESTRUTURA DE DADOS ADICIONAL

O lote passa a possuir:

• status_cancelamento  
• motivo_cancelamento  
• data_cancelamento  
• valor_estorno_esperado  
• data_estorno_confirmado  

Valores possíveis para status_cancelamento:

• nao_cancelado  
• cancelado_sem_pagamento  
• cancelado_aguardando_estorno  
• cancelado_estornado  

────────────────

TIMELINE DO LOTE

A timeline deve incorporar o evento:

• Cancelado  

Podendo refletir:

• cancelamento direto  
• cancelamento com estorno pendente  
• cancelamento finalizado após estorno  

────────────────

REGISTRO DE MOVIMENTAÇÃO

O cancelamento deve gerar movimentação obrigatória contendo:

• tipo_evento: cancelamento  
• motivo  
• data  
• responsável  
• indicação de impacto financeiro  

────────────────────────────────────────

NOVA PARTE 4.12  
Separação entre Lotes Ativos e Lotes Encerrados

O que acontece nesta parte:

• remoção de lotes finalizados do dashboard principal  
• criação de área dedicada para lotes encerrados  
• separação entre lotes finalizados por venda e cancelados  
• manutenção de acesso completo aos dados históricos  
• preservação de navegação para ficha do lote  

Objetivo:

garantir clareza operacional no dashboard e preservar histórico sem interferir na leitura do estado atual da operação.

Resultado esperado:

• dashboard passa a exibir apenas lotes ativos  
• lotes encerrados ficam organizados em área própria  
• sistema diferencia claramente finalização por venda e por cancelamento  
• histórico permanece acessível e consultável  

Impacto técnico: 🟡 Médio / Alto

────────────────

REGRAS DO DASHBOARD PRINCIPAL

O dashboard principal deve exibir apenas:

• lotes em_transito  
• lotes em_estoque  

Não devem aparecer:

• lotes finalizados  
• lotes cancelados  

────────────────

ÁREA DE LOTES ENCERRADOS

Deve existir uma área dedicada contendo:

• lotes finalizados por venda  
• lotes cancelados  

Essa área deve permitir:

• consulta completa do lote  
• leitura do resultado final  
• acesso ao histórico  
• distinção clara do tipo de encerramento  

────────────────

CLASSIFICAÇÃO INTERNA DOS ENCERRADOS

Os lotes encerrados devem ser classificados em:

• Finalizados (venda total)  
• Cancelados  

O cancelado deve manter distinção entre:

• sem pagamento  
• com estorno pendente  
• com estorno concluído  

────────────────────────────────────────

REORGANIZAÇÃO DA ETAPA

A partir desta expansão, a sequência passa a ser:

• 4.11 — Cancelamento de Lote  
• 4.12 — Separação de Dashboard e Encerrados  
• 4.13 — Relatórios  
• 4.14 — Validação da Etapa  
• 4.15 — Auditoria Técnica  

────────────────────────────────────────

DIRETRIZ FINAL

Esta expansão deve ser absorvida como evolução estrutural da ETAPA 4.

Não deve interromper a execução atual da Parte 4.4.

Deve ser lida pelo implementador (Lucas) para conhecimento antecipado, sem alteração no fluxo corrente.

────────────────────────────────────────

Felipe Andrade  
Líder de Engenharia — AuraLabs  

Darlan  
Direção — AuraLabs  

────────────────────────────────────────