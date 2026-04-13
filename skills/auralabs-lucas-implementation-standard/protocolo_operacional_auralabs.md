# SKILL — PROTOCOLO OPERACIONAL DE EXECUÇÃO AURALABS

## Objetivo

Esta skill define o fluxo oficial de trabalho entre:

- Direção / Estrutura (AuraLabs)
- Implementação (Lucas)
- Validação final (Darlan)

Seu objetivo é padronizar:

- criação de documentos
- solicitação de plano técnico
- aprovação de execução
- entrega para teste
- correções
- entrega formal da parte
- auditoria
- commit de etapa

Esta skill deve ser aplicada em qualquer projeto conduzido sob o método AuraLabs.

---

## Princípio central

Nenhuma execução deve acontecer fora de fluxo.

O fluxo correto é:

1. Direção consolida a etapa
2. Direção define a parte
3. Lucas analisa a parte
4. Lucas devolve plano técnico
5. Direção aprova ou corrige o plano
6. Lucas executa
7. Lucas entrega para teste
8. Darlan testa
9. Se houver erro, Lucas corrige
10. Se estiver aprovado, Lucas registra entrega formal da parte
11. Direção absorve e libera a próxima parte
12. Ao final da etapa, Direção conduz auditoria
13. Ao final da auditoria, Direção gera commit de etapa

---

## Regra operacional obrigatória

Você deve respeitar rigorosamente a separação entre os momentos do fluxo.

### Não confundir:

- documento geral da etapa
- documento da parte
- plano técnico
- execução
- entrega para teste
- correção
- entrega final da parte
- auditoria
- commit de etapa

Cada um possui:
- cabeçalho próprio
- finalidade própria
- tópicos obrigatórios
- assinatura obrigatória

---

## Regra crítica de autorização

### Documento da etapa
- define arquitetura e direção da etapa
- não autoriza execução

### Documento da parte
- define escopo da parte
- solicita plano técnico
- não autoriza execução

### Plano técnico do Lucas
- explica como a parte será executada
- ainda não autoriza execução

### Execução técnica
- só pode começar após aprovação explícita do plano técnico

---

## Regra de papel de cada agente

### Direção / AuraLabs
Responsável por:
- definir etapa
- definir parte
- preservar coerência
- aprovar ou corrigir plano técnico
- absorver entrega
- liberar próxima parte
- conduzir auditoria
- gerar commit de etapa

### Lucas / Implementação
Responsável por:
- analisar a parte
- devolver plano técnico
- executar somente após autorização
- entregar para teste
- corrigir se necessário
- registrar entrega final da parte

### Darlan
Responsável por:
- testar a execução
- validar se o resultado atende
- apontar necessidade de correção quando houver

---

## Regras de documentação

Todos os documentos devem:

- usar cabeçalho padronizado
- conter apenas o conteúdo compatível com seu momento
- evitar mistura de etapas
- manter rastreabilidade
- possuir assinatura obrigatória no rodapé

Sem assinatura, o documento é inválido.

---

# 1. PADRÃO — DOCUMENTO GERAL DA ETAPA

## Finalidade
Consolidar a estrutura técnica e estratégica da etapa.

## Cabeçalho obrigatório

PROJETO: [Nome do Projeto]  
CLIENTE: [Nome do Cliente]

ETAPA: [Número e nome da etapa]

MOMENTO: Estrutura Técnica Consolidada da Etapa  
STATUS: ETAPA DEFINIDA PARA EXECUÇÃO  
EXECUÇÃO TÉCNICA: ❌ NÃO AUTORIZADA

ORIGEM: Desenvolvimento — AuraLabs  
DESTINO: Implementação — Lucas

RESPONSÁVEL TÉCNICO  
[Nome]  
[Cargo] — AuraLabs

SUPERVISÃO  
[Darlan]  
Direção — AuraLabs

## Corpo obrigatório

O documento geral da etapa deve conter, quando aplicável:

- VISÃO GERAL DA ETAPA
- PRINCÍPIO CENTRAL DO MÓDULO / ETAPA
- REGRAS ESTRUTURAIS OBRIGATÓRIAS
- ENTIDADES PRINCIPAIS
- REGRAS DE NEGÓCIO
- RELAÇÕES ENTRE ENTIDADES
- DECISÕES ESTRUTURAIS CONSOLIDADAS
- DIVISÃO TÉCNICA DA ETAPA
- RESULTADO FINAL ESPERADO DA ETAPA
- CONCLUSÃO

## Regras

- Não conter código
- Não conter instrução de implementação detalhada
- Não pular decisões estruturais
- Não deixar ambiguidade sobre funcionamento da etapa

## Assinatura obrigatória

[Nome]  
Responsável Técnico — AuraLabs

Darlan  
Direção — AuraLabs

---

# 2. PADRÃO — DOCUMENTO DA PARTE

## Finalidade
Definir formalmente uma parte da etapa e solicitar plano técnico do Lucas.

## Cabeçalho obrigatório

PROJETO: [Nome do Projeto]  
CLIENTE: [Nome do Cliente]

ETAPA: [Número e nome da etapa]  
PARTE: [Número e nome da parte]

MOMENTO: Plano Técnico da Parte

ORIGEM: Desenvolvimento — AuraLabs  
DESTINO: Implementação — Lucas

STATUS: Plano técnico solicitado  
EXECUÇÃO TÉCNICA: ❌ NÃO AUTORIZADA

RESPONSÁVEL TÉCNICO  
[Nome]  
[Cargo] — AuraLabs

SUPERVISÃO  
Darlan  
Direção — AuraLabs

## Corpo obrigatório

- OBJETIVO DA PARTE
- ESCOPO DA PARTE
- ESTRUTURA FUNCIONAL OBRIGATÓRIA
- REGRAS IMPORTANTES
- INTEGRAÇÃO COM ETAPAS / PARTES ANTERIORES
- ARQUIVOS PROVÁVEIS ENVOLVIDOS
- RESULTADO ESPERADO
- AÇÃO SOLICITADA
- MODELO DE RESPOSTA ESPERADO

## Regras

- Não conter solução técnica final
- Não autorizar execução
- Não misturar plano com execução
- Deve deixar claro o que entra e o que não entra na parte

## Assinatura obrigatória

[Nome]  
Responsável Técnico — AuraLabs

Darlan  
Direção — AuraLabs

---

# 3. PADRÃO — TEXTO DO LUCAS PARA DESCREVER COMO EXECUTARÁ (PLANO TÉCNICO)

## Finalidade
Responder à parte com a estratégia de implementação.

## Cabeçalho obrigatório

[PROJETO] / Etapa [X] / Parte [X.X] / Entrega para Aprovação

## Corpo obrigatório

### Plano

**O que será feito:**  
[descrição]

**Onde será feito:**  
[arquivos, módulos, tabelas, templates, endpoints, etc.]

**Impacto:**  
[baixo / médio / alto + justificativa]

**Resultado esperado:**  
[o que passará a existir ao final da execução]

### Estrutura técnica / modelagem / arquitetura proposta  
[quando necessário]

### Impacto Técnico Esperado  
[efeitos no sistema, dependências, integrações]

### Validação Prevista  
[como ele pretende validar antes de entregar para teste]

### Riscos, se houver  
[riscos reais e objetivos]

### Observação Técnica  
[opcional]

## Regras

- Não executar nada ainda
- Não pedir aprovação implícita
- Não misturar plano com entrega
- Deve antecipar dependências, impacto e risco
- Deve ser específico, não genérico

## Assinatura obrigatória

Lucas  
Implementação Técnica — AuraLabs

---

# 4. PADRÃO — TEXTO DO LUCAS PARA ENTREGA PARA TESTE

## Finalidade
Informar que executou a parte e liberar o resultado para teste do Darlan.

## Cabeçalho obrigatório

[PROJETO] / Etapa [X] / Parte [X.X] / Entrega para Teste

## Corpo obrigatório

### Execução
[visão geral do que foi implementado]

### O que foi implementado
- [item]
- [item]

### Arquivos alterados
- [arquivo]
- [arquivo]

### Ajustes adicionais realizados
- [item]
- [item]

### Teste

**Como validar:**  
- [passo]
- [passo]

**Cenários esperados:**  
- [resultado esperado]
- [resultado esperado]

**Validação executada:**  
- [sintaxe, teste local, leitura de dados, etc.]

**Observação:**  
[pontos importantes ou limites]

## Regras

- Deve ser voltado ao teste
- Não deve ser confundido com entrega final da parte
- Deve dizer claramente como validar
- Deve listar arquivos alterados
- Deve informar o que ainda depende da validação de Darlan

## Assinatura obrigatória

Lucas  
Implementação Técnica — AuraLabs

---

# 5. PADRÃO — TEXTO DO LUCAS QUANDO HÁ CORREÇÃO

## Finalidade
Registrar correção após teste ou observação.

## Cabeçalho obrigatório

[PROJETO] / Etapa [X] / Parte [X.X] / Correção [N] / Entrega para Teste

## Corpo obrigatório

### O que foi ajustado
- [item]
- [item]

### Arquivos alterados
- [arquivo]
- [arquivo]

### Validação executada
- [item]
- [item]

### Observações
- [explicações adicionais, se necessárias]

## Regras

- Não repetir toda a entrega anterior
- Ser direto e rastreável
- Informar apenas o que foi alterado na correção
- Deve continuar voltado para teste

## Assinatura obrigatória

Lucas  
Implementação Técnica — AuraLabs

---

# 6. PADRÃO — TEXTO DE ENTREGA FINAL DA PARTE

## Finalidade
Registrar formalmente a conclusão da parte após teste aprovado.

## Cabeçalho obrigatório

[PROJETO] / Etapa [X] / Parte [X.X] / Entrega

## Corpo obrigatório

### Registro da entrega
[A parte foi entregue e registrada em documento, se aplicável]

### O que foi feito
- [item]
- [item]

### Onde foi feito
- [arquivo + papel do arquivo]
- [arquivo + papel do arquivo]

### Resultado entregue
- [resultado]
- [resultado]

### Correções realizadas dentro da própria parte
- [correção]
- [correção]

### Validação registrada
- [sintaxe]
- [teste]
- [aprovação visual / funcional]
- [limites conscientemente reservados, se houver]

### Ajuste de postura registrado
[opcional, quando houver mudança de processo, formato ou comportamento]

## Regras

- Deve consolidar toda a parte
- Não deve trazer execução nova
- Deve funcionar como histórico oficial da parte
- Deve ser mais completo que a entrega para teste
- Deve ser rastreável

## Assinatura obrigatória

Lucas  
Implementação Técnica — AuraLabs

Darlan  
Direção — AuraLabs

---

# 7. PADRÃO — TEXTO DE CORREÇÃO DA DIREÇÃO

## Finalidade
A Direção só intervém com correção quando houver erro, inconsistência, desalinhamento ou necessidade de ajuste.

## Estrutura esperada

- STATUS DA PARTE
- ERRO / PONTO DE CORREÇÃO
- AJUSTE SOLICITADO
- MANUTENÇÃO DO FLUXO

## Regras

- Se não houver erro, não fazer análise extensa
- Se estiver correto, apenas absorver e seguir para a próxima parte
- A Direção não deve transformar aprovação em parecer crítico desnecessário

---

# 8. PADRÃO — AUDITORIA FINAL DA ETAPA

## Finalidade
Validar a etapa como um todo ao final de todas as partes.

## Estrutura obrigatória

- CONTEXTO DA AUDITORIA
- PARTES AUDITADAS
- VERIFICAÇÃO ESTRUTURAL
- VERIFICAÇÃO FUNCIONAL
- VERIFICAÇÃO DE CONSISTÊNCIA
- VERIFICAÇÃO DE REAPROVEITAMENTO
- VERIFICAÇÃO DE UX/UI
- PENDÊNCIAS, SE HOUVER
- RESULTADO DA AUDITORIA

## Resultado possível

- ETAPA AUDITADA E APROVADA
ou
- ETAPA COM AJUSTES OBRIGATÓRIOS

## Assinatura obrigatória

[Nome]  
Responsável Técnico — AuraLabs

Darlan  
Direção — AuraLabs

---

# 9. PADRÃO — COMMIT DE ETAPA

## Finalidade
Encerrar formalmente a etapa após auditoria aprovada.

## Estrutura obrigatória

- IDENTIFICAÇÃO DA ETAPA
- RESUMO DO QUE A ETAPA ENTREGOU
- PARTES EXECUTADAS
- DECISÕES CONSOLIDADAS
- VALIDAÇÃO FINAL
- STATUS DE ENCERRAMENTO
- PRÓXIMO DESTINO DO PROJETO

## Assinatura obrigatória

[Nome]  
Responsável Técnico — AuraLabs

Darlan  
Direção — AuraLabs

---

# 10. REGRAS CRÍTICAS

## 10.1 Nenhum documento é válido sem:
- cabeçalho correto
- momento correto
- status correto
- tópicos obrigatórios
- assinatura no rodapé

## 10.2 Assinatura do Lucas é obrigatória
Erro recorrente identificado:
- Lucas enviou documentos sem assinatura

Regra consolidada:
- todo texto do Lucas deve terminar com nome e função

Sem isso:
- documento incompleto
- registro inválido para padrão AuraLabs

## 10.3 A direção não analisa quando não há erro
Se a parte foi entregue corretamente:
- absorver
- aprovar
- seguir para a próxima parte

Sem parecer desnecessário.

## 10.4 Execução sem autorização é inválida
Se o plano técnico ainda não foi aprovado:
- a execução não está autorizada

## 10.5 Cada texto precisa respeitar seu momento
- plano não é entrega
- entrega para teste não é entrega final
- correção não é nova parte
- auditoria não é commit

---

# 11. INSTRUÇÃO FINAL DA SKILL

Ao atuar dentro desse protocolo, você deve:

- preservar rigor de fluxo
- manter padrão documental
- bloquear execução prematura
- exigir assinatura
- evitar textos fora do momento correto
- agir com disciplina metodológica
- manter rastreabilidade total do projeto

O padrão não é opcional.
Ele é parte da arquitetura operacional da AuraLabs.