# Finanças — Web Service de Controle Financeiro Pessoal

## 1. Sobre o projeto

O Finanças é uma API REST desenvolvida em PHP utilizando o framework Slim. Seu objetivo é permitir o gerenciamento de um controle financeiro pessoal, possibilitando cadastrar receitas, despesas e categorias, além de consultar o saldo e acompanhar os lançamentos financeiros.

O projeto foi desenvolvido como atividade individual.

## 2. Tecnologias utilizadas

- **PHP:** linguagem utilizada no desenvolvimento da API.
- **Slim Framework:** framework utilizado para criação das rotas e gerenciamento das requisições HTTP.
- **Composer:** gerenciamento das dependências do projeto.
- **JSON:** formato utilizado para envio e recebimento de dados e armazenamento das informações.
- **PHPUnit:** ferramenta utilizada para realizar os testes automatizados.
- **Postman:** ferramenta utilizada para testar as requisições da API.

## 3. Estrutura do projeto

```text
financias/
│
├── public/
│   └── index.php
│
├── src/
│   └── app.php
│
├── storage/
│   ├── categorias.json
│   └── lancamentos.json
│
├── tests/
│   └── FinanciasTeste.php
│
├── vendor/
│
├── composer.json
├── composer.lock
└── README.md
```

### Descrição das pastas

- **public:** contém o arquivo responsável por iniciar a aplicação.
- **src:** contém o código principal da API e suas rotas.
- **storage:** armazena os dados das categorias e dos lançamentos em arquivos JSON.
- **tests:** contém os testes automatizados realizados com PHPUnit.
- **vendor:** contém as dependências instaladas pelo Composer.

## 4. Funcionalidades

### Categorias

A API permite:

- Cadastrar categorias de receitas e despesas.
- Listar todas as categorias.
- Consultar uma categoria pelo ID.
- Atualizar uma categoria.
- Excluir uma categoria, desde que não possua lançamentos vinculados.

### Lançamentos financeiros

A API permite:

- Cadastrar receitas e despesas.
- Listar todos os lançamentos.
- Consultar um lançamento pelo ID.
- Atualizar lançamentos.
- Excluir lançamentos.
- Filtrar lançamentos por período, categoria ou tipo.

### Resumo financeiro

A API permite:

- Consultar o saldo financeiro atual.
- Consultar o total de receitas e despesas de um período específico.

## 5. Endpoints da API

Considere que a API está sendo executada em `http://localhost:8081`.

### Geral

| Método | Rota | Descrição |
|---|---|---|
| GET | `/status` | Verifica se a API está funcionando. |

### Categorias

| Método | Rota | Descrição |
|---|---|---|
| GET | `/categorias` | Lista as categorias. |
| GET | `/categorias/{id}` | Consulta uma categoria. |
| POST | `/categorias` | Cadastra uma categoria. |
| PUT | `/categorias/{id}` | Atualiza uma categoria. |
| DELETE | `/categorias/{id}` | Exclui uma categoria. |

### Lançamentos

| Método | Rota | Descrição |
|---|---|---|
| GET | `/lancamentos` | Lista os lançamentos. |
| GET | `/lancamentos/{id}` | Consulta um lançamento. |
| POST | `/lancamentos` | Cadastra um lançamento. |
| PUT | `/lancamentos/{id}` | Atualiza um lançamento. |
| DELETE | `/lancamentos/{id}` | Exclui um lançamento. |

### Filtros

| Método | Rota | Descrição |
|---|---|---|
| GET | `/lancamentos?tipo=receita` | Filtra por receitas. |
| GET | `/lancamentos?tipo=despesa` | Filtra por despesas. |
| GET | `/lancamentos?categoria=1` | Filtra por categoria. |
| GET | `/lancamentos?data_inicial=2026-11-01&data_final=2026-11-30` | Filtra por período. |

### Resumo financeiro

| Método | Rota | Descrição |
|---|---|---|
| GET | `/resumo` | Consulta o resumo financeiro atual. |
| GET | `/resumo/periodo?data_inicial=2026-11-01&data_final=2026-11-30` | Consulta o resumo de um período. |

## 6. Exemplos de requisições

### Cadastrar uma categoria

**POST** `/categorias`

```json
{
    "nome": "Alimentação",
    "tipo": "despesa"
}
```

### Cadastrar um lançamento

**POST** `/lancamentos`

```json
{
    "descricao": "Compra no mercado",
    "valor": 250.00,
    "data": "2026-11-10",
    "tipo": "despesa",
    "categoria": 2
}
```

## 7. Regras de negócio

O sistema possui algumas regras para garantir a consistência dos dados:

1. O valor de um lançamento deve ser maior que zero.
2. O tipo do lançamento deve ser receita ou despesa.
3. O tipo do lançamento deve corresponder ao tipo da categoria.
4. A categoria informada deve existir.
5. O nome de uma categoria não pode ser duplicado.
6. A data do lançamento deve ser válida e obrigatória.
7. Uma categoria não pode ser excluída enquanto possuir lançamentos vinculados.

## 8. Códigos HTTP utilizados

| Código | Significado |
|---|---|
| 200 | Requisição realizada com sucesso. |
| 201 | Recurso criado com sucesso. |
| 400 | Requisição inválida. |
| 404 | Recurso não encontrado. |
| 409 | Conflito com uma regra do sistema. |
| 422 | Dados enviados não passaram pela validação. |

## 9. Como instalar e executar

### Pré-requisitos

- PHP instalado.
- Composer instalado.
- Terminal ou ambiente de desenvolvimento.

### Instalação

Clone o repositório e entre na pasta do projeto:

```bash
git clone URL_DO_REPOSITORIO
cd financias
```

Instale as dependências:

```bash
composer install
```

### Executar a API

Inicie o servidor local:

```bash
php -S localhost:8081 -t public public/index.php
```

A API estará disponível em:

```text
http://localhost:8081
```

## 10. Testes automatizados

Os testes foram desenvolvidos utilizando PHPUnit, verificando o funcionamento das rotas, as operações de cadastro, consulta, atualização e exclusão, além das validações e respostas da API.

Para executar os testes, utilize:

```bash
./vendor/bin/phpunit tests/FinanciasTeste.php
```

O PHPUnit apresenta o resultado dos testes, indicando quais passaram e quais apresentaram falhas.

## 11. Armazenamento dos dados

As informações são armazenadas em arquivos JSON dentro da pasta `storage`. Dessa forma, os dados permanecem salvos mesmo após o encerramento do servidor local.

## 12. Autor

Projeto desenvolvido individualmente como atividade acadêmica de desenvolvimento de Web Services utilizando PHP e Slim Framework.