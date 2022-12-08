# bcbento-server

This package provides the back-end for Boston College Libraries' "bento" search page.

## Prerequisites

The server requires:

* PHP 8.1+
* the [composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos) dependency manager
* the [Symfony CLI tool](https://symfony.com/download)
* a Boston College Eagle VPN connection

## Installation

Use [composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos) to install:

```bash
git clone https://github.com/BCLibraries/bcbento-server.git
cd bcbento-server
git fetch
git checkout update-to-php-8.1
composer install
```

Create an _.env.local_ file in the root directory and add any missing or changed values from the _.env_ file. A working 
.env file can be found in [bento documentation on the BC Libraries wiki](https://bcwiki.bc.edu/display/UL/Bento+search#Bentosearch-Configuration).

## Starting the development server

Use the Symfony local server for development:

```bash
symfony serve
```

## Querying the server

Services are provided via [GraphQL](https://graphql.org/). You can query the development server interactively using GraphiQL at [http://127.0.0.1:8000/graphiql](http://127.0.0.1:8000/graphiql) (update the port number as appropriate).

### Example queries

Use thr GraphiQL documentation browser to see all available queries and parameters. Some example queries include:

#### Catalog search
```graphql
  searchCatalog(keyword: "otters") {
    total
    docs {
      id
      title
      creator
      availability {
        availableCount
        totalCount
        libraryName
        callNumber
        locationName
      }
    }
    facets {
      name
      values {
        value
        count
      }
    }
  }
```

#### Librarians
```graphql
  recommendLibrarian(keyword: "history") {
    docs {
      id
      name
      email
      image
      subjects
    }
  }
```

#### Best bets

```graphql
  bestBet(keyword: "history") {
    title
    ... on LocalBestBet {
      displayText
      link
    }
  }
```

## Indexing

The ElasticSearch indexes are built from the command line.

### Librarians

```shell
# Build the librarians index
./bin/console librarians:build

# Edit or create a librarian, using their LibGuides ID
./bin/console librarians:edit 1234567
```

### Website

```shell
# Build the website index
./bin/console website:build

# Index all website pages
./bin/console website:index --all

# Index website page updated since last indexing job
./bin/console website:index
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)