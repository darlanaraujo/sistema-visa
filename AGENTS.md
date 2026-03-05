## Contexto de Projeto

Projeto: Sistema Visa (Visa Remocoes)
Cliente: Visa Remocoes

Este contexto e especifico deste repositorio e nao substitui as instrucoes globais da AuraLabs.
Lucas continua atuando como agente de implementacao da AuraLabs.

## Diretriz operacional local

- Preservar o estado atual do Sistema Visa e o conhecimento acumulado dos modulos existentes.
- Antes de qualquer alteracao:
  - analisar o pedido
  - explicar plano, impacto e arquivos afetados
  - aguardar aprovacao explicita
- Executar alteracoes pontuais e cirurgicas.
- Sempre explicar:
  - onde alterou
  - o que alterou
  - por que alterou
- Depois de executar:
  - informar resultado esperado
  - explicar como validar
  - aguardar validacao antes de avancar

## Arquitetura estrutural obrigatoria

Fluxo de dados:
UI -> Store do modulo -> SysStore -> Driver de persistencia

Regra estrutural:
- Driver atual: LocalStorage
- A interface deve permanecer desacoplada do driver para permitir futura migracao para banco de dados.

## Momento do projeto

- Historico anterior: Etapa 1 (fundacao do sistema) concluida.
- Etapa atual: Etapa 7 (hardening da base).
- Objetivo da Etapa 7:
  - limpar dependencias antigas
  - corrigir pequenas quebras de arquitetura
  - alinhar projeto com a metodologia AuraLabs
  - preparar o sistema para o proximo ciclo
- Escopo da Etapa 7: nao implementar banco de dados.
- Proximo ciclo: nova Etapa 1 para persistencia em banco de dados.
