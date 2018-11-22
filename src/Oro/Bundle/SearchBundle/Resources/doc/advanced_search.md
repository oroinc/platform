API advanced search
====================

REST API allow to create advanced search queries.

Parameters for APIs requests:

 - **query** - search string

REST API url: http://domail.com/api/rest/latest/search/advanced

REST API work with get request only.

Result
====================

Request returns an array with data:

 - **records_count** - the total number of results (without `offset` and `max_results`) parameters
 - **count** - count of records in current request
 - **data** - array with data. Data consists of values:
     - **entity_name** - class name of entity
     - **record_id** - id of record from this entity
     - **record_string** - the title of this record
     - **record_url** - the given URL for this record
     - **selected_data** - data from fields that have been explicitily selected in the select clause (optional)

 Query language
====================

Keywords
--------

### select

Include field values, taken from the search index, as an additional
 "selected_data" section in the search result items.
 You can select one or more fields to be attached to search results.
 The name of the field should consist the type prefix, otherwise the default
 ``text`` type will be used.
```
select text.field_name
select (text.first_field_name, text.second_field_name)
```

You can use fieldname aliasing, as known in SQL, for example:

```
select text.field_1 as name, text.field_2 as author
```

You can use fieldname aliasing, as known in SQL, for example:

```
select (text.field_1 as name, text.field_2 as author)
```
Note that parentheses are mandatory.

### from

List of entity aliases to search from. It can be one alias or group. Examples:
```
from one_alias
from (first_alias, second_alias)
```
### where

Auxiliary keyword for visual separation `from` block from search parameters

### and, or

Used to combine multiple clauses, allowing you to refine your search. Syntax:
```
and field_type field_name operator value
or field_type field_name operator value
```
If field type not set, then text field type will be used.

### offset

Allow to set offset of first result.

### max_results

Set results count for the query.

### order_by

Allow to set results order. Syntax:
```
order_by field_type field_name direction
```
If field type was not set, then text field will be assigned. Direction - `ASC`, `DESC`.
If direction is not assigned then will be used `ASC` direction.

### aggregate

Allow to provide aggregated data based on a search query, as an additional "aggregated_data" section in the search
result. To build aggregated data you need to call separated function of search query.

Syntax:
```
aggregate field_type field_name aggregate_function aggregating_name
```

Supported next aggregation functions:
* **count**
* **sum**
* **max**
* **min**
* **avg**

Field types
-----------

User should specify field type in query string. By default, if type is not set, it will be used text type. Supported field types:
* **text**
* **integer**
* **decimal**
* **datetime**

Operators
-----------

Different field types support different operators in `where` block.

### For string fields

* **~ (CONTAINS)** - operator `~` is used for set text field value. If search value is string, it must be quoted.
Examples:
```
name ~ value
name ~ "string value"
```

* **!~ (NOT CONTAINS)** - operator `!~` is used for search strings without value.
If search value is string, it must be quoted. Examples:
```
name !~ value
name !~ "string value"
```

* **like** - operator `like` is used for finding records with specified substring in any position (`LIKE %value%` statement behaviour). If the search value is a multi-word string that contains whitespaces, it should be enclosed in quotes.
Examples:
```
name like value
name like "string value"
```

* **notlike** - operator `notlike` is used for finding records without specified substring in any position (`NOT LIKE %value%` statement behaviour). If the search value is a multi-word string that contains whitespaces, it should be enclosed in quotes.
Examples:
```
name notlike value
name notlike "string value"
```

### For numeric fields

* **= (EQUALS)** - operator `=` is used for search records where field matches the specified value.
Examples:
```
integer count = 100
decimal price = 12.5
datetime create_date = "2013-01-01 00:00:00"
```

* **!= (NOT EQUALS)** - operator `!=` is used for search records where field does not matches the specified value.
Examples:
```
integer count != 5
decimal price != 45
datetime create_date != "2012-01-01 00:00:00"
```
* **>, <, <=, >=** - Operators are used to search for the records that have the specified field must be `greater`,
`less`, `less than or equals` or `greater than or equals` of the specified value. Examples:
```
integer count >= 5
decimal price < 45
datetime create_date > "2012-01-01 00:00:00"
```

* **in** - operator `in` is used for search records where field in the specified set of data.
Examples:
```
integer count in (5, 10, 15, 20)
decimal price in (12.2, 55.25)
```

* **!in** - operator `!in` is used for search records where field not in the specified set of data.
Examples:
```
integer count !in (1, 3, 5)
decimal price !in (2.1, 55, 45.4)
```

### Query brackets.

User can combine operators in search query with brackets.

Examples:

```
from oro_test where (owner ~ john and (integer count > 10 or float price = 10)) or (owner ~ mary and (integer count > 5 or float price = 150))
```

Query examples
--------------

* Search by demo products where name contains string `opportunity` and where price greater than `100`.
```
from demo_product where name ~ opportunity and double price > 100
```

* Search and return entity data plus name and description of demo products.
```
select (name, description) from demo_product
```

* Search by all entities where integer field count not equals `10`.
```
integer count != 10
```

* Search by all entities where text field `all_text` not contains string `opportunity`
```
all_text !~ "opportunity"
```

* Select `10` results from `demo_products` and `demo_categories` entities where text field description contains `test`,
order `ASC` by text field name and offset first result to `5`.
```
from (demo_products, demo_categories) where description ~ test order_by name offset 5 max_results 10
```
