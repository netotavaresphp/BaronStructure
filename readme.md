
## Sobre o Projeto

Este projeto viabiliza a padronização e legibilidade dos projetos em PHP, no padrão MVC. Além de seguir boas práticas de folders arquitecture, temos também, opção de executar outras linguagens, via CLI.
Espero que lhe seja útil. 

## Características

- **Arquitetura MVC** - Separação clara de responsabilidades
- **Sistema de Rotas** - Roteamento flexível e intuitivo
- **Container de Dependências** - Gerenciamento eficiente de dependências
- **HTTP Request/Response** - Manipulação moderna de requisições
- **View Engine** - Sistema de templates simples e eficaz
- **Configuração Centralizada** - Gerenciamento de configurações da aplicação
- **Autoloading PSR-4** - Carregamento automático de classes via Composer

## Requisitos

- PHP 7.4 ou superior
- Composer
- Servidor Web (Apache/Nginx) ou PHP Built-in Server

## Instalação

Clone o repositório:
```bash
git clone https://github.com/seu-usuario/myStructure.git
cd myStructure
```

Instale as dependências:
```bash
composer install
```

Configure o servidor web ou utilize o servidor embutido do PHP:
```bash
php -S localhost:8000 -t public
```

Acesse a aplicação em seu navegador:
```
http://localhost:8000
```

## Estrutura do projeto

```
myStructure/
├── app/                    # Código da aplicação
│   ├── Controllers/        # Controllers da aplicação
│   └── Views/             # Templates de visualização
│       ├── home/          # Views específicas
│       └── layouts/       # Layouts reutilizáveis
├── bin/                   # Scripts executáveis
├── bootstrap/             # Inicialização da aplicação
│   ├── app.php           # Bootstrap principal
│   └── helpers.php       # Funções auxiliares
├── config/                # Arquivos de configuração
│   ├── app.php           # Configurações gerais
│   └── paths.php         # Definição de caminhos
├── core/                  # Núcleo do framework
│   ├── Container/        # Container de injeção de dependências
│   ├── Exceptions/       # Exceções customizadas
│   ├── Http/            # Componentes HTTP
│   ├── Process/         # Processamento de requisições
│   ├── Routing/         # Sistema de roteamento
│   └── View/            # Engine de visualização
├── public/               # Pasta pública (document root)
│   └── index.php        # Ponto de entrada da aplicação
├── routes/               # Definição de rotas
│   └── web.php          # Rotas web
├── storage/              # Arquivos gerados pela aplicação
│   ├── cache/           # Cache
│   ├── logs/            # Logs
│   └── uploads/         # Uploads de arquivos
└── vendor/               # Dependências do Composer
```

## Uso básico

### Criando uma rota

Adicione rotas no arquivo `routes/web.php`:

```php
$router->get('/exemplo', 'ExemploController@index');
$router->post('/exemplo/criar', 'ExemploController@store');
```

### Criando um controller

Crie um controller em `app/Controllers/`:

```php
<?php

namespace App\Controllers;

class ExemploController
{
    public function index()
    {
        return view('exemplo/index', [
            'titulo' => 'Minha Página'
        ]);
    }
    
    public function store()
    {
        // Lógica para salvar dados
    }
}
```

### Criando uma view

Crie uma view em `app/Views/`:

```php
<!-- app/Views/exemplo/index.php -->
<h1><?= $titulo ?></h1>
<p>Conteúdo da página</p>
```

## Configuração

As configurações da aplicação estão localizadas na pasta `config/`:

- `config/app.php` - Configurações gerais da aplicação
- `config/paths.php` - Definição de caminhos do sistema

## Desenvolvimento

### Executando o servidor de desenvolvimento

```bash
php -S localhost:8000 -t public
```

### Estrutura de arquivos de configuração

Edite os arquivos em `config/` para personalizar o comportamento da aplicação.

## Contribuindo

Contribuições são bem-vindas. Para contribuir com o projeto:

Faça um fork do repositório

Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)

Commit suas mudanças (`git commit -m 'Adiciona nova feature'`)

Push para a branch (`git push origin feature/MinhaFeature`)

Abra um Pull Request