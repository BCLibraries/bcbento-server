# Data sources

The bento search queries a number of different data stores. Some are on campus and maintained locally while others are external services.

## Services

### Primo (catalog, articles, video)

Catalog, article, and video search is provided by Primo using the [Primo brief search REST API](https://developers.exlibrisgroup.com/primo/apis/docs/primoSearch/R0VUIC9wcmltby92MS9zZWFyY2g=/). The three search types correspond to our three Primo search "tabs" and scopes.

### Librarian recommender

Librarian searches are performed against our on-campus Elasticsearch instance. Librarian searches use two indices:

1. First the search is performed against an Elasticsearch index containing our local collection (titles, authors, and subjects). The records in that index are tagged with [taxonomy terms](https://bcwiki.bc.edu/display/UL/LibGuides+Taxonomy) taken from [a taxonomy developed by the University of Michigan libraries](https://www.lib.umich.edu/browse/categories/). Terms are assigned to records based on their call numbers. The query against this index returns ranked lists of:
 
    * the most common taxonomy terms in records containing the search term
    * the most common locations and collections in records containing the search term
    
2. These ranked taxonomy terms and locations/collections are used to build a query against the librarians index in Elasticsearch, where librarians are assigned terms from the UMich taxonomy. Librarian records that cross a relevance threshold are returned.

For example, [a search for "otters"](https://library.bc.edu/search/?any=otters) will first query the all-records index and return common taxonomy terms ("Biology", "Zoology", "Natural Resources and Environment") and locations/collections ("Educational Resource Center"). Because most of our "otter" resources are held in the ERC, that term will be weighted the most. Searching the librarians index using these weighted terms will likely return the ERC librarian.

### FAQ

FAQ searches are performed against LibAnswers using their API.

### Typeahead (autocomplete)

Typeahead data is stored in the same Elasticsearch all-document index used to generate recommender terms (see above).  
