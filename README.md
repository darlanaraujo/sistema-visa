Sistema Visa — Documentação Base

1. Visão Geral do Sistema

O Sistema Visa é um sistema de gestão desenvolvido para atender às necessidades da empresa Visa Remoções – Depósito de Salvados, com foco em controle operacional, financeiro e estratégico do negócio de compra e revenda de lotes de salvados provenientes de seguradoras.

O sistema foi concebido de forma modular, permitindo evolução gradual, organização clara das regras de negócio e separação lógica entre funcionalidades.

⸻

2. Organização dos Chats do Projeto

Para manter clareza, rastreabilidade e organização do desenvolvimento, o projeto foi dividido em múltiplos chats, cada um com um objetivo específico:

2.1 Chat de Comentários (Base Conceitual)

Objetivo:
	•	Centralizar decisões estratégicas
	•	Definir regras de negócio
	•	Documentar conceitos, fluxos e premissas
	•	Registrar dúvidas não relacionadas diretamente a código

Este documento consolida tudo o que foi definido neste chat.

2.2 Chats Técnicos Específicos

Chats separados para:
	•	Estruturação de telas (ex: login)
	•	Modelagem de dados
	•	Regras financeiras
	•	Implementações futuras

Cada chat técnico trata apenas de execução, não de definição conceitual.

⸻

3. Dados Institucionais da Empresa
	•	Nome Fantasia: Visa Remoções – Depósito de Salvados
	•	CNPJ: 17.686.570/0001-80
	•	Atividade Principal:
Compra e revenda de lotes de salvados oriundos de seguradoras

3.1 Modelo de Aquisição de Lotes
	•	As seguradoras enviam cotações por e-mail
	•	Diversas empresas participam
	•	O lote é vendido para quem ofertar o maior valor

⸻

4. Conceito Central do Sistema

O sistema foi pensado para responder a três perguntas principais:
	1.	O que foi comprado? (controle de lotes)
	2.	Quanto custou e quanto rendeu? (controle financeiro)
	3.	Qual a real lucratividade do negócio? (análise e relatórios)

Tudo no sistema gira em torno do lote de salvados como entidade principal.

⸻

5. Módulos do Sistema

5.1 Módulo de Controle de Lotes

Responsável por:
	•	Cadastro de lotes adquiridos
	•	Origem do lote (seguradora)
	•	Data de compra
	•	Valor de aquisição
	•	Descrição geral do lote
	•	Status do lote (em estoque, vendido, parcialmente vendido)

O lote é a unidade central de rastreabilidade do sistema.

⸻

5.2 Módulo Financeiro

Responsável por:

Entradas
	•	Vendas de itens ou do lote
	•	Outras receitas vinculadas

Saídas
	•	Compra do lote
	•	Custos operacionais (frete, pátio, taxas, comissões)

Apuração
	•	Lucro bruto
	•	Lucro líquido
	•	Margem por lote

Todo lançamento financeiro deve estar vinculado a um lote, direta ou indiretamente.

⸻

5.3 Módulo Administrativo
	•	Cadastro da empresa
	•	Parâmetros do sistema
	•	Usuários e permissões (futuro)

⸻

6. Princípios de Modelagem

6.1 Simplicidade
	•	Evitar complexidade desnecessária
	•	Sistema voltado para uso prático no dia a dia

6.2 Rastreabilidade Total
	•	Cada valor financeiro deve ter origem clara
	•	Cada lucro deve ser explicável

6.3 Evolução Gradual
	•	Sistema preparado para crescer
	•	Funcionalidades avançadas entram em fases futuras

⸻

7. Fluxo Macro do Negócio no Sistema
	1.	Recebimento de cotação da seguradora
	2.	Aquisição do lote
	3.	Cadastro do lote no sistema
	4.	Registro de custos associados
	5.	Venda total ou parcial do lote
	6.	Registro das receitas
	7.	Apuração automática do resultado

⸻

8. O que NÃO é objetivo neste momento
	•	Integrações automáticas com seguradoras
	•	Emissão fiscal
	•	Controle contábil completo
	•	Estoque unitário detalhado (fase inicial focada em lote)

Esses pontos ficam fora do escopo inicial, mas não são descartados no futuro.

⸻

9. Papel desta Documentação

Este documento serve como:
	•	Base oficial do projeto
	•	Referência para decisões futuras
	•	Guia para desenvolvimento técnico
	•	Registro fiel do que foi definido conceitualmente

Qualquer mudança estrutural no sistema deve refletir primeiro aqui.

⸻

10. Próximos Passos Naturais
	•	Consolidar modelos de dados
	•	Validar regras financeiras
	•	Definir telas principais
	•	Evoluir para relatórios e indicadores

⸻

Documento base criado a partir do Chat de Comentários — Sistema Visa