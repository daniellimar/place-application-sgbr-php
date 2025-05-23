# 🗺️ ApiPlaces

Projeto Laravel 12 com PHP 8 utilizando Docker e PostgreSQL. Essa API serve como base para cadastro e gerenciamento de
locais (places).

## 🚀 Requisitos

- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)

## ⚙️ Configuração do Ambiente

### 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/api-places.git
cd api-places
```

### 2. Copie o arquivo de ambiente

```bash
cp .env-example .env
```

### 3. Configure a conexão com o banco de dados no .env

Certifique-se de que as variáveis abaixo estão configuradas corretamente:

```bash
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=api-places
DB_USERNAME=root
DB_PASSWORD=mysecretpassword
```

### 4. Criação automática da base de dados

A base de dados api-places será criada automaticamente pelo serviço postgres do docker-compose.yml, com as seguintes
variáveis de ambiente:

```bash
environment:
  POSTGRES_DB: api-places
  POSTGRES_USER: postgres
  POSTGRES_PASSWORD: mysecretpassword
```

### 5. Construa os containers

```bash
docker compose build
```

### 6. Inicie os containers

```bash
docker compose up -d
```

### 7. Instale as dependências e configure o Laravel (opcional)

```bash
docker compose exec app bash
composer install
php artisan key:generate
```

### 🌐 Acesso à Aplicação

A API estará disponível em:

```bash
http://localhost:8000
```