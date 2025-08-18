# 📸 Camagru

Camagru é uma aplicação web fullstack inspirada em redes sociais de compartilhamento de imagens. O objetivo é permitir que usuários capturem fotos com a webcam ou façam upload de imagens, adicionem sobreposições (stickers), editem e publiquem suas criações para que outros usuários possam visualizar, curtir e comentar.

Este projeto foi desenvolvido com foco em segurança, responsividade e boas práticas de containerização, utilizando PHP, HTML, CSS, JavaScript e Docker.

---

## ✨ Funcionalidades

### 🔐 Usuário
- Cadastro com verificação por e-mail
- Login com validação de senha segura
- Redefinição de senha via e-mail
- Edição de perfil (usuário, e-mail, senha)
- Preferência de notificação por e-mail
- Logout disponível em todas as páginas

### 🖼️ Galeria
- Galeria pública com todas as imagens dos usuários
- Curtidas e comentários disponíveis para usuários logados
- Notificações por e-mail ao receber um novo comentário
- Paginação com no mínimo 5 imagens por página

### 🎥 Editor de Imagens
- Captura de imagens via webcam
- Upload de imagens como alternativa à webcam
- Aplicação de sobreposições (stickers)
- Processamento da imagem final feito no servidor
- Visualização e exclusão de imagens pessoais

---

## 🧱 Tecnologias Utilizadas

- **Frontend:** HTML5, CSS3, JavaScript puro
- **Backend:** PHP (sem frameworks externos)
- **Banco de dados:** MariaDB
- **Webserver:** NGINX
- **Containerização:** Docker & Docker Compose
- **Gerenciamento de variáveis:** `.env`
- **Gerenciamento de ambiente:** `Makefile`

---

## 🛡️ Segurança

O sistema foi desenvolvido com foco em segurança:

- Hash de senhas com `password_hash`
- Proteção contra SQL Injection (prepared statements)
- Escapando saídas para evitar XSS
- Tokens CSRF nos formulários
- Validações completas no frontend e backend

---

## 🛠️ Como Rodar o Projeto

> É necessário ter o **Docker** e o **Make** instalados.

### 🔄 Subir o ambiente de desenvolvimento

```bash
make
# ou, de forma explícita:
make all
```

### 🔁 Rebuild completo (com rebuild das imagens)

```bash
make re
```

### 🧹 Parar os containers

```bash
make down
```

### 🧼 Limpeza leve (containers + imagens não utilizadas)

```bash
make clean
```

### 💣 Limpeza total (containers, imagens, redes, volumes órfãos)

```bash
make fclean
```

### 🧩 Variáveis de Ambiente

O Makefile utiliza o arquivo .env com as configurações do projeto. Exemplo:
```bash
MYSQL_ROOT_PASSWORD=sua_senha_root
MYSQL_DATABASE=camagru
MYSQL_USER=nome_de_usuario
MYSQL_PASSWORD=sua_senha
PMA_USER=nome_de_usuario
PMA_PASSWORD=sua_senha
PMA_ARBITRARY=1
DB_PORT=3306
DB_SERVER=mariadb
SENDGRID_API_KEY=api_key_do_sendgrid
```

### 🎯 Objetivo de aprendizado
Este projeto foi desenvolvido com foco em:

- Manipulação de DOM
- Processamento de imagens
- Segurança web (CSRF, XSS, SQLi)
- Design responsivo e compatibilidade cross-browser

---

## ✅ Feito
- Dockerizar (NGINX, MariaDB, phpmyadmin, php)
- Configurar .env
- Configurar banco de dados
- Conectar banco de dados ao PHP
- Criar `index.php`
- Criar página de cadastro
- Criar página de login
- Fazer o CSS da página de login
- Fazer o logotipo
- Conectar API do serviço de e-mail
- Criar página de confirmação de cadastro
- Validar todos os formulários no front e no back-end
- Hash de senhas no banco de dados (`password_hash`)
- Prevenir SQL Injection (uso de `mysqli_prepare`)
- Escapar saída para prevenir XSS
- Implementar CSRF tokens

---

## 🟡 Em Progresso
<!-- Coloque aqui as tarefas que está fazendo no momento -->
### 👤 Funcionalidades de Usuário
- [ ] Fluxo de redefinição de senha (formulário, token, email)
- [ ] Logout funcional em qualquer página
- [ ] Página de edição de perfil (nome de usuário, e-mail, senha)
- [ ] Preferência de notificações por e-mail

---

## 🔲 A Fazer

### 🖼️ Galeria
- [ ] Página pública com imagens de todos os usuários
- [ ] Paginação (mínimo 5 imagens por página)
- [ ] Curtidas (visíveis apenas para usuários logados)
- [ ] Comentários (visíveis e usáveis apenas por logados)
- [ ] Enviar e-mail ao autor quando há comentário (padrão: ativado)

### 🎥 Editor de Imagens
- [ ] Página de edição (restrita a usuários logados)
- [ ] Preview da webcam
- [ ] Lista de imagens superponíveis (stickers)
- [ ] Botão de captura desativado até escolher uma imagem
- [ ] Upload de imagem alternativa à webcam
- [ ] Processamento no servidor da imagem final (PHP: GD/ImageMagick)
- [ ] Exibir miniaturas das capturas anteriores
- [ ] Permitir deletar somente imagens do próprio usuário

### 🧱 Layout e Compatibilidade
- [ ] Layout com header, main e footer
- [ ] Design responsivo (desktop e mobile)
- [ ] Compatível com Firefox (>= 41) e Chrome (>= 46)

---

## ✨ Bônus (após tudo 100% funcional)

- [ ] AJAX nas interações com servidor (login, comentários, likes)
- [ ] Preview ao vivo da imagem final (frontend)
- [ ] Paginação infinita na galeria
- [ ] Compartilhamento de imagens nas redes sociais
- [ ] Criação de GIFs animados com múltiplas fotos

