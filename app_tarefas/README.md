# Minhas Tarefas — versão MySQL

Aplicação para `tarefas.franklem.com`, usando HTML/CSS/JavaScript no frontend e PHP + MySQL no backend.

## Recursos
- Cadastro e login
- Senhas armazenadas com `password_hash`
- Sessão PHP com cookie HttpOnly
- Proteção CSRF para alterações
- Cada usuário acessa somente suas próprias tarefas
- Sincronização via MySQL entre computador e celular
- Criar, editar, excluir e concluir tarefas
- Data, prioridade e categoria
- Busca, filtros e ordenação
- Tema claro/escuro
- Layout responsivo

## Instalação na Hostinger
1. Crie o subdomínio `tarefas.franklem.com`.
2. Crie um banco MySQL e um usuário no painel da hospedagem.
3. Abra o phpMyAdmin do banco e importe `database.sql`.
4. Edite `config.php` e preencha:
   - DB_HOST
   - DB_NAME
   - DB_USER
   - DB_PASS
5. Faça upload de todos os arquivos e da pasta `api` para a pasta raiz do subdomínio.
6. Garanta que o SSL/HTTPS esteja ativo.
7. Acesse o subdomínio e crie sua conta.

## Segurança
Não publique capturas contendo a senha do banco. O `.htaccess` incluído bloqueia acesso web direto a `config.php`, `database.sql` e `README.md` em servidores Apache/LiteSpeed compatíveis.

## Observação
Se o objetivo for uso estritamente pessoal, depois de criar sua conta você pode remover/ desativar o cadastro público em `api/auth.php`. Uma evolução futura pode incluir recuperação de senha por e-mail, tarefas recorrentes, notificações e PWA instalável.
