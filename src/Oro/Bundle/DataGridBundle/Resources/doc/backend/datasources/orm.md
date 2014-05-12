ORM datasource
===============

Overview
--------

This datasource provide adapter to allow access data from doctrine orm using doctrine query builder.
You can configure query using `query` param under source tree. This query will be converted via [YamlConverter](../../../../Datasource/Orm/QueryConverter/YamlConverter.php) to doctrine `QueryBuilder` object.

Example
-------

``` yaml
datagrid:
    DATAGRID_NAME_HERE:
        source:
            type: orm
            query:
                select:
                    - g.id
                    - g.label
                from:
                    - { table: OroCRMContactBundle:Group, alias: g }
```

Query hints
-----------
The following example shows how [query hints](https://doctrine-orm.readthedocs.org/en/latest/reference/dql-doctrine-query-language.html#query-hints) can be set:

``` yaml
datagrid:
    DATAGRID_NAME_HERE:
        source:
            type: orm
            query:
                select:
                    - partial g.{id, label}
                from:
                    - { table: OroCRMContactBundle:Group, alias: g }
            hints:
                - HINT_FORCE_PARTIAL_LOAD
```

If you need to set hint's value you can use the following syntax:

``` yaml
datagrid:
    DATAGRID_NAME_HERE:
        source:
            type: orm
            query:
                select:
                    - c
                from:
                    - { table: %orocrm_contact.entity.class%, alias: c }
                join:
                    left:
                        - { join: c.addresses, alias: address, conditionType: WITH, condition: 'address.primary = true' }
                        - { join: address.country, alias: country }
                        - { join: address.region, alias: region }
            hints:
                - { name: HINT_CUSTOM_OUTPUT_WALKER, value: %oro_translation.translation_walker.class%}
```

