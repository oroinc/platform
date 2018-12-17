Query Designer Configuration
----------------------------
The Query Designer behavior can be tuned using configuration files. These files should be named `query_designer.yml` and can be located in any bundle in `Resources/config/oro` directory. The following code snippet shows the main structure of `query_designer.yml` file.

``` yaml
query_designer:
    filters:
        # put configuration of filters here
    grouping:
        # put configuration of grouping columns here
    aggregates:
        # put configuration of aggregating functions here
```

The all available configuration options you can find in [Configuration.php](../../../QueryDesigner/Configuration.php) file.

Filters configuration
---------------------
This section describes how you can configure the list of filters are shown on the result page, for instance on report result grid, and intended to filter resulting data.
The configuration of [default filters](../../config/oro/query_designer.yml) describes filters for general data types, such as `string`, `integer`, `money`, `percent` etc. For instance, take a look on configuration of a filter used to filter textual data:
``` yaml
query_designer:
    filters:
        string: # filter name, you can use any string here
            applicable: [{type: string}, {type: text}]
            type:       string
            query_type: [all]
```
The `applicable` attribute describes rules then a filter will be used. In this case the filter will be used if an entity field data type is a `string` or `text`. Each item in the `applicable` array can have the following attributes:

 - `type` - field data type
 - `field` - field name
 - `entity` - entity name, for example `OroUserBundle:User` or `Oro\Bundle\UserBundle\Entity\User`
 - `identifier` - true/false, check if the field is a primary key

For instance if you need to use a special filter for `name` field of `User` entity, you can use the following applicable condition: `{entity: OroUserBundle:User, field: name}`
The `type` attribute sets the identifier of a filter UI control. All existing controls you can find in [FilterBundle](../../../../FilterBundle/Resources/config/filters.yml). The value of `type` attribute in `query_designer.yml` should be equal of the value of `type` attribute of `oro_filter.extension.orm_filter.filter` tag.
The `query_type` attribute sets the types of queries this filter will be available. The `all` word is reserved and it means the filter will be available in all queries.

How modify existing filter from your bundle
-------------------------------------------
Lets figure out your bundle introduced new data type, for instance `ShortMoney`, and you want to use existing `number` filter for it. In this case you need to add the following `query_designer.yml` file in your bundle:
``` yaml
query_designer:
    filters:
        number:
            applicable: [{type: ShortMoney}]
```
This will add an additional condition to the `applicable` attribute of the existing `number` filter.

Grouping configuration
----------------------
Currently the configuration of the grouping columns has only one attribute. It is `exclude` attribute. Using this attribute you can specify which fields cannot be used in `GROUP BY` SQL clause. By [default](../../config/oro/query_designer.yml) the following data types are not available for grouping: `array`, `object`. Here is an example of grouping configuration:
``` yaml
query_designer:
    grouping:
        exclude: [{type: array}, {type: object}]
```
Each item in the `exclude` array can have the following attributes:

 - `type` - field data type
 - `field` - field name
 - `entity` - entity name, for example `OroUserBundle:User` or `Oro\Bundle\UserBundle\Entity\User`
 - `identifier` - true/false, check if the field is a primary key

Aggregating functions configuration
-----------------------------------
This section describes how you can configure which aggregating functions will be available in the query designer. By default the QueryDesigner bundle does not provide configuration for any aggregating function. The following example shows how aggregating functions for numeric data type can be added:
``` yaml
query_designer:
        number:
            applicable: [{type: integer}, {type: smallint}, {type: bigint}, {type: decimal}, {type: float}, {type: money}, {type: percent}]
            functions:
                - { name: Count, expr: COUNT($column), return_type: integer }
                - { name: Sum,   expr: SUM(CASE WHEN ($column IS NOT NULL) THEN $column ELSE 0 END) }
                - { name: Avg,   expr: AVG(CASE WHEN ($column IS NOT NULL) THEN $column ELSE 0 END) }
                - { name: Min,   expr: MIN($column) }
                - { name: Max,   expr: MAX($column) }
            query_type: [report]
```
This example adds `COUNT`, `SUM`, `AVG`, `MIN` and `MAX` aggregation functions for all numeric data types. These functions will be available only if the query type is `report`.
Each item in the `applicable` array can have the following attributes:

 - `type` - field data type
 - `field` - field name
 - `entity` - entity name, for example `OroUserBundle:User` or `Oro\Bundle\UserBundle\Entity\User`
 - `parent_entity` - the name of parent entity, for example `OroUserBundle:User` or `Oro\Bundle\UserBundle\Entity\User`
 - `identifier` - true/false, check if the field is a primary key
