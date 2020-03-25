# bcbento-server

This package provides the back-end for Boston College Libraries' "bento" search page.

## Installation

Use [composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos) to install:

```bash
git clone https://github.com/BCLibraries/bcbento-server.git
cd bcbento-server
composer install

```

### Requirements

* PHP 7.1+
* A Boston College VPN connection

## Starting the development server

Use the Symfony local server for development:

```bash
./bin/console server:start
```

### Querying the server

Services are provided via [GraphQL](https://graphql.org/). You can query the development server interactively using [GraphiQL](http://127.0.0.1:8000/graphiql). 

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)