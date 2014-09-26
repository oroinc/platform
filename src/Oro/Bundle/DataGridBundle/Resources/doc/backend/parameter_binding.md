Parameter binding
=================

## Overview

Parameter binding is used to fill datasource with parameters from datagrid. For example
[ORM datasource](./datasources/orm.md) is working query on top of Doctrine ORM and QueryBuilder is used
to build query to database. Using parameter binding option in orm datasource you can confiure mapping between
parameters of datagrid and parameters of query.

## Configuration Syntax

``` yml
datagrid:
    acme-demo-datagrid:
        source:
            type: orm
            query:
                select:
                    - u
                from:
                    { table: AcmeDemoBundle:User, alias:u }
            where:
                and:
                    - u.group = :group_id
            bind_parameters:
                # Get parameter "group_id" from datagrid and set it's value to "group_id" parameter in datasource query
                - group_id
```

In case if name of parameters in grid and query not match, you can pass associative array of parameters, where key will
be name of parameter in query, and value - name of parameter if grid:

``` yml
datagrid:
    acme-demo-grid:
        source:
            type: orm
            query:
                select:
                    - u
                from:
                    { table: AcmeDemoBundle:User, alias:u }
            where:
                and:
                    - u.group = :group_id
            bind_parameters:
                # Get parameter "groupId" from datagrid and set it's value to "group_id" parameter in datasource query
                group_id: groupId

To pass parameter "groupId" to the grid use this format when rendering grid in template:

``` twig
{{ dataGrid.renderGrid('acme-demo-datagrid', {'groupId': entityId}) }}
```

Or pass them to [DatagridManager](./../../Datagrid/DatagridManager.php) directly:

``` php
$datagridManager->getDatagrid('acme-demo-datagrid', ['groupId' => $entityId]);
```

There is also available full format for declaring parameters binding:

``` yml
    bind_parameters:
        data_in: # option string key will be interpreted as name of parameter in query
            path: _parameters.groupId # it will reference to parameter groupId in key _parameters of parameter bag.
            default: [0] # some default value, will be used if parameter is not passed
            type: array # type applicable with Doctrine: Doctrine\DBAL\Types\Type::getType()
```

``` yml
    bind_parameters:
        -
            name: # name of parameter in query
            path: _parameters.groupId # it will reference to parameter groupId in key _parameters of parameter bag.
            default: [0] # some default value, will be used if parameter is not passed
            type: array # type applicable with Doctrine: Doctrine\DBAL\Types\Type::getType()
```

## Support of parameter binding by datasource

Datasource must implement [ParameterBinderAwareInterface](./../../Datasource/ParameterBinderAwareInterface.php)
to support "bind_parameters" option.

## Parameter binder class

Parameter binder class must implements [ParameterBinderInterface](./../../Datasource/ParameterBinderInterface.php) and
depends on datasources implementation.

Example of usage:

``` php
// get parameter "name" from datagrid parameter bag and add it to datasource
$queryParameterBinder->bindParameters($datagrid, ['name']);

// get parameter "id" from datagrid parameter bag and add it to datasource as parameter "client_id"
$queryParameterBinder->bindParameters($datagrid, ['client_id' => 'id']);

// get parameter "email" from datagrid parameter bag and add it to datasource, all other existing
// parameters will be cleared
$queryParameterBinder->bindParameters($datagrid, ['email'], false);
```

## Parameter binding listener

[DatasourceBindParametersListener](./../../EventListener/DatasourceBindParametersListener.php) is responsible
for run binding of datasource parameters. It checks whether datasource implements
[ParameterBinderInterface](./../../Datasource/ParameterBinderInterface.php) and whether it has "bind_parameters" option.

If grid configuration is applicable, that parameters configuration specified in "bind_parameters" will be passed to
datasource method _bindParameters_.
