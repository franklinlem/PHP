FRANKLEM EDITORIAL 1.5.8

Objetivo
Coletar títulos, links e resumos disponibilizados por feeds RSS e organizá-los como pautas privadas no WordPress. Não publica posts e não copia artigos completos.

Instalação
1. Plugins > Adicionar plugin > Enviar plugin.
2. Envie franklem-editorial.zip, instale e ative.
3. Abra Pautas > Configurações.
4. Clique em Executar coleta agora.
5. Verifique os itens em Pautas.

Fontes iniciais
- Agência Brasil: Política e Esporte.
- Pesquisa FAPESP: Ciência.
- Olhar Digital: Tecnologia.

Versão 1.2
- Ignora automaticamente ofertas, cupons e chamadas promocionais.
- Calcula uma pontuação inicial de relevância editorial para novas pautas.
- Permite editar os termos bloqueados em Pautas > Configurações.

Versão 1.3
- Integração piloto com OpenAI usando gpt-5.6-luna e gpt-image-1-mini.
- Chave criptografada e nunca reexibida.
- Redação e revisão em chamadas separadas.
- Imagem horizontal em qualidade média.
- Cria somente rascunhos e limita a seis por dia.

Versão 1.4
- Pesquisa a pauta na web antes da redação.
- Exige pelo menos duas fontes independentes.
- Registra no post todos os links usados na apuração.
- Mantém a recusa quando a notícia não puder ser confirmada.

Versão 1.4.1
- Conta a fonte RSS original no mínimo editorial.
- Exige duas fontes no total e três para temas sensíveis.
- Reaproveita por seis horas a apuração de tentativas anteriores.
- Identifica a etapa exata de uma eventual recusa.

Versão 1.5
- Seleciona diariamente até uma pauta de cada editoria e cria somente rascunhos.
- Executa cada notícia separadamente para reduzir o risco de timeout da hospedagem.
- Mantém a automação de IA desligada até ser ativada pelo administrador.
- Exibe relatório de notícias geradas, recusas e custo estimado.
- Limita a produção automática a três rascunhos por dia.
- Inclui limpeza das pautas promocionais antigas.

Versão 1.5.6
- Permite criar rascunhos com uma única fonte primária confiável.
- Mantém a notícia como rascunho para revisão humana antes da publicação.
- Adiciona filtros de editoria e data da fonte à lista de pautas.

Versão 1.5.7
- Permite configurar manualmente de 1 a 20 rascunhos por dia.
- Preserva o limite escolhido pelo administrador nas atualizações do plugin.
- Adiciona um atalho para alterar o limite ao lado do consumo diário.
- Distribui a geração automática de forma equilibrada entre as editorias quando o limite for maior que seis.

Versão 1.5.8
- Impede que uma atualização do plugin marque todos os rascunhos como modificados ao mesmo tempo.
- Exibe situação, data de criação e data de publicação em colunas separadas na lista de posts.
- Adiciona filtro explícito para rascunhos, publicados, pendentes e agendados.
- Ordena a lista pela data da notícia, sem usar a última modificação como referência principal.

Segurança editorial
- A coleta automática começa desativada.
- A geração automática com IA também começa desativada.
- Links repetidos são ignorados.
- Pautas antigas em rascunho são enviadas à lixeira conforme a retenção configurada.
- Sempre confirme fatos em outra fonte antes de redigir.
