# Oro\Bundle\SearchBundle\Api\Model\SearchItem

## ACTIONS

### get_list

Retrieve a collection of search records.

{@request:json_api}
The `entityUrl` link in the `links` section contains a URL of an entity associated with a search record. 

Example:

```JSON
{
  "data": {
    "type": "search",
    "id": "users-1",
    "links": {
      "entityUrl": "http://my-site.com/admin/user/view/1"
    }
  }
}
```

#### **searchQuery** filter

This filter is used to specify the search query.

The simple query consists of a field, followed by an operator, followed by one or more values surrounded by parentheses.
For example:

```
businessUnit = 2
```

This query will find records with `businessUnit` search field equals to `2`. It uses the **businessUnit** field, the `=` (EQUALS) operator,
and the 2 as a value.

```
businessUnit in (1, 3)
```

This query will find records with `businessUnit` search field equals `1` or `3`. It uses the **businessUnit** field, the `in` operator,
and the `1` and `3` values.

A more complex query might look like this:

```
username ~ "John" and id < 10
```

This query will find records that contain the `John` word in userName search field, and with minimal id search field that is less than 10.
It uses two expressions, `username ~ "John"` and `id < 10`, that are joined by the logical `and` operator.

The parentheses in complex queries can be used to enforce the precedence of operators. For example:

```
(username ~ "admin" or birthday > "2000-01-01") and username ~ "John"
```

**Notes:**

* the space symbol must delimit an operator from a field and a value.
* a string value that contains a space symbol must be enclosed by double quotes (`"`).
* for boolean values use `0` for `false` and `1` for `true`.
* a datetime value must be enclosed by double quotes (`"`) and formatted as `YYYY-MM-DD hh:mm:ss`, where:

  - `YYYY` - four-digit year
  - `MM` - two-digit month (01=January, etc.)
  - `DD` - two-digit day of month (01 through 31)
  - `hh` - two digits of hour (00 through 23)
  - `mm` - two digits of minute (00 through 59)
  - `ss` - two digits of second (00 through 59)

<br />
Keywords:

| Keyword | Description |
|---------|-------------|
| `and` | Logical AND. Used to combine multiple clauses allowing you to refine your search. |
| `or` | Logical OR. Used to combine multiple clauses allowing you to expand your search. Also see `in` operator which can be a more convenient way to search for multiple values of a field. |

<br />
Common operators:

| Operator | Description |
|----------|-------------|
| `=` (EQUALS) | The value of the specified field exactly matches the specified value. |
| `!=` (NOT EQUALS) | The value of the specified field does not match the specified value. |
| `in` (IN) | The value of the specified field is one of multiple specified values. The values are specified as a comma-delimited list surrounded by parentheses. The expression `field in (1, 2)` is equal to `field = 1 or field = 2`. |
| `!in` (NOT IN) | The value of the specified field is not one of multiple specified values. The values are specified as a comma-delimited list surrounded by parentheses. The expression `field !in (1, 2)` is equal to `field != 1 and field != 2`. |
| `exists` (EXISTS) | The specified field exists for a product. An example: `field exists`. |
| `notexists` (NOT EXISTS) | The specified field does not exist for a product. An example: `field notexists`. |

<br />
Operators for string values:

| Operator | Description |
|----------|-------------|
| `~` (CONTAINS) | The value of the specified field does "fuzzy" match the specified value. |
| `!~` (NOT CONTAINS) | The value of the specified field does not "fuzzy" match for the specified value. |
| `like` (LIKE) | The value of the specified field contains the specified substring in any position. |
| `notlike` (NOT LIKE) | The value of the specified field does not contain the specified substring in any position. |
| `starts_with` (STARTS WITH) | The value of the specified field starts with the specified substring. |

<br />
Operators for numeric and date values:

| Operator | Description |
|----------|-------------|
| `>` (GREATER THAN) | The value of the specified field is greater than the specified value. |
| `>=` (GREATER THAN OR EQUALS) | The value of the specified field is greater than or equal to the specified value. |
| `<` (LESS THAN) | The value of the specified field is less than the specified value. |
| `<=` (LESS THAN OR EQUALS) | The value of the specified field is less than or equal to the specified value. |

<br />

The list of fields that can be used in the search query can be get with `searchentities` API resource
or at the description of the `searchQuery` filter of searchable API resource. 

The **allText** is a particular field that can be used to do an overall full-text search. The value of this field usually
contains values of all text fields.

#### **searchText** filter

The string filter for searching data in the **allText** field.

#### **aggregations** filter

This filter is used to request aggregated data.

This filter should contain comma delimited definitions of aggregations.
The definition of each aggregation can be `fieldName aggregatingFunction`
or `fieldName aggregatingFunction resultName`. An example is
`minimalPrice sum sumOfMinimalPrices,productType count`.

If `resultName` is not specified, it is built automatically as `fieldName` + `aggregatingFunction` with
uppercased first character, e.g., the result name for `productType count` will be `productTypeCount`.

The list of fields for which the aggregated data can be requested can be get with `searchentities` API resource.

Aggregating functions:

| Function | Description                                                                                           |
|----------|-------------------------------------------------------------------------------------------------------|
| `count` | Counts the number of values that are extracted from the search index.                                 |
| `sum` | Sums up numeric values that are extracted from the search index.                                      |
| `avg` | Computes the average of numeric values that are extracted from the search index.                      |
| `min` | Returns the minimum value among numeric or date values that are extracted from the search index.      |
| `max` | Returns the maximum value among the numeric or date  values that are extracted from the search index. |

<br />
The aggregated data is returned in the **aggregatedData** field of **meta** section of the response.

The response for the `count` aggregating function is an array. Each element of this array is an object with
2 properties, **value** and **count**.
The **value** property contains a value for which the count is calculated.
The **count** property contains the number of occurrences of the value in the search result.

The response for other aggregating functions is a number.

An example:

```JSON
{
    "meta": {
        "aggregatedData": {
            "minimalPriceSum": 123.45,
            "productTypeCount": [
                { "value": "simple", "count": 10 },
                { "value": "configurable", "count": 5 }
            ]
        }
    }
}
```
The list of fields that can be used in aggregations can be get with `searchentities` API resource
or at the description of the `aggregations` filter of searchable API resource.

#### **sort** filter

This filter is used to sort the result data.

If the request has `searchQuery`, `searchText`, or `aggregations` filters, the value of the `sort` filter should match an available search field name.

The list of fields that can be used in sort filter when the query use search request can be get with `searchentities` API resource
or at the description of the `sort` filter of searchable API resource.

{@/request}

## FIELDS

### entityName

The name of an entity associated with a search record.

### entity

An entity associated with a search record.

## FILTERS

### searchText

A string to be searched.

### searchQuery

A search query.

### aggregations

The filter that is used to request aggregated data.

### entities

The list of entity types to search. By default, all entities are searched.
