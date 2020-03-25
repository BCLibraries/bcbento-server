# GraphQL

The bento client and server communicate using GraphQL

## What is GraphQL?

> [GraphQL is a query language for APIs and a runtime for fulfilling those queries with your existing data.](https://graphql.org/)

GraphQL offers several advantages for bento:

* There are existing well-supported client ([Apollo](https://www.apollographql.com/)) and server ([GraphQLite](https://graphqlite.thecodingmachine.io/docs/features.html)) packages.
* GraphQL is standard across languages and platforms, preventing lock-in.
* The built-in IDE and documentation provided by GraphiQL makes it easy to explore the interface.

## The GraphiQL interface

The server has an embedded version of [GraphiQL](https://github.com/graphql/graphiql), the GraphQL IDE. You can access GraphiQL to query the local server at http://localhost:8000/graphiql. Use the _Docs_ pane to investigate the BCBento schema.

