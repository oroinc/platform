Mixin
===========

## Overview

Mixin is a datagrid that contains additional(common) information for use by other datagrids.

## Configuration Syntax

``` yml
datagrids:
    
    # configuration mixin with column, sorter and filter for an entity identifier
    acme-demo-common-identifier-datagrid-mixin:
        source:
            type: orm
            query:
                select:
                    # alias that will be replaced by an alias of the root entity
                    - __root_entity__.id as identifier
        columns:
            identifier:
                frontend_type: integer
        sorters:
            data_name: identifier
        filters:
            columns:
                identifier:
                    type: number
                    data_name: identifier

    acme-demo-user-datagrid:
        # one or several mixins
        mixins:
            - acme-demo-datagrid-mixin
            - ...
            - ...
        source:
            type: orm
            query:
                from:
                    { table: AcmeDemoBundle:User, alias:u }
```
