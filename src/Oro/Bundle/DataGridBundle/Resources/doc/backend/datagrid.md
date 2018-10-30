Datagrid
========

Table of content
-----------------
- [Overview](#overview)
- [Getting Started](#getting-started)
- [Advanced grid configuration (How to's)](./advanced_grid_configuration.md)
- [Configuration reference](./configuration_reference.md)
- [Editable data grid cells](./editable_grid_cells.md)
- [Implementation](./implementation.md)
- [Extendability](#extendability)


## Overview
Datagrid is a table oriented representation of the data from some datasource.
It is configured in a YAML file, that should be placed in the `Resources/config/oro` folder of your bundle and named `datagrids.yml`.
This file should contain root node `datagrids` and each grid configuration should be placed under it.

## Getting Started
#### Configuration File
First of all to define own datagrid you should create configuration file as described in "overview" section.
After that, you have to choose identifier of yours future grid and declare it by adding associative array with identifier as key.
e.g.
``` yaml
datagrids:
    acme-demo-datagrid:     # grid identifier
        ...                 # configuration will be here
``` 

#### Datasource
When it's done, next step is to configure datasource, basically it's similar array under `source` node.
You have to choose datasource type and properly configure  depending on it. For further details [see](./datasources.md).
e.g.
``` yaml
datagrids:
    acme-demo-datagrid:
        source:
            type: orm  # datasource type
            query:
                ....   # some query configuration
```

##### Datasource as Service
Other than the `query` yaml-oriented provider, ORM datasource supports an alternative `query_builder` service-oriented provider. 
Basically it is possible to use any arbitrary method that returns a valid `Doctrine\ORM\QueryBuilder` instance.

``` php
// @acme_demo.user.repository
public class UserRepository 
{
    // ....

    /**
    * @return Doctrine\ORM\QueryBuilder
    */
    public function getUsersQb()
    {
        return $this->em->createQueryBuilder()
            ->from('AcmeDemoBundle:User', 'u')
            ->select('u')
            // ->where(...)
            // ->join(...)
            // ->orderBy(...)
        ;
    }
}

``` 

In the datagrid configuration just provide the service and method name:

``` yaml
datagrids:
    acme-demo-datagrid:
        source:
            type: orm  # datasource type
            query_builder: "@acme_demo.user.repository->getUsersQb"
```

##### Parameters Binding

If datasource supports parameters binding, additional option "bind_parameters" can be specified. For example

``` yaml
datagrids:
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
                group_id: groupId
```

Parameters binding is also supported while using the `query_builder` notation for the ORM data source. 
Each binding will call `->setParameter('group_id', group_id)` automatically upon the provided builder. 

[More about parameters binding](./parameter_binding.md).

#### Columns and Properties
Next step is columns definition. It's array as well as other parts of grid configuration.
 Root node for columns is `columns`, the definition key should be a unique column identifier, the value is an array of the column configuration.
  The same for properties, but root node is `properties`.

Property is something similar to column but it has no frontend representation.
Properties can be used to pass additional data generated for each row, for example URLs of row actions.

**Note:** _column identifier is used for some suggestion, so best practice is to use identifier similar with data identifier (e.g field name in DQL)_

**Note:** _Usually row identifier property should be added for correct work, but for simplest grids it's excess_

Configuration format is different depending on column type, but there are list of keys shared between all types.

- `type`  - backend formatter type (`field` - by default)
- `label` - column title (translated on backend, translation should be placed in "messages" domain)
- `frontend_type` - frontend formatters that will process the column value (`string` - by default)
- `editable` - is column editable on frontend (`false` - by default)
- `data_name` - data identifier (column name suggested by default)
- `renderable` - should column be rendered (`true` - by default)
- `order` - number of column's position, allows to change order of the columns over [Datagrid Settings](../frontend/datagrid_settings.md) and save it in [Grid View](./extensions/grid_views.md) (by default is not defined and the columns are rendered in the order in which they are declared in the configuration)
- `required` - if it is `true` the column can not be hidden over [Datagrid Settings](../frontend/datagrid_settings.md) (by default is `false`)
- `manageable` - if it is `false` the column does not appear in [Datagrid Settings](../frontend/datagrid_settings.md) (by default is `true`)
- `shortenableLabel` - could column label be abbreviated or shortened with ellipsis (`true` - by default)

For detailed explanation [see](./extensions/formatter.md).

So lets define few columns:
``` yaml
datagrids:
    acme-demo-datagrid:
        source:
            type: orm
            query:
                select: [ o.firstName, o.lastName, o.age ]
                from: 
                    - { table: AcmeDemoBundle:Entity, alias: o } #defining table class using FQCN
#                    - { table: '%acme_demo.entity.entity_name.class%', alias: o } #defining table class using parameter
        columns:
            firstName:                                   # data identifier will be taken from column name
                label: acme.demo.grid.columns.firstName  # translation string
            lastName:
                label: acme.demo.grid.columns.firstName  # translation string
            age:
                label: acme.demo.grid.columns.age        # translation string
                frontend_type: number                    # needed for correct l10n (e.g. thousand, decimal separators etc)
``` 

#### Sorting
After that you may want to make your columns sortable. Sorting configuration should be placed under `sorters` node.
 In basic sorter implementation, configuration takes `columns` and `default` keys.
Basically it's array of column names where value is sorter configuration.
 There is one required value `data_name` that responsible of knowledge on which data grid should do sorting.

Lets make all columns sortable:
``` yaml
datagrids:
    acme-demo-datagrid:
        ...                                 # definition from previous examples
        sorters:
            columns:
                firstName:
                    data_name: o.firstName
                lastName:
                    data_name: o.lastName
                age:
                    data_name: o.age
            default:
                lastName: DESC              # Default sorting, allowed values ASC|DESC
``` 

For detailed explanation [see](./extensions/sorter.md).

#### Final Step
Final step for this tutorial is to add grid to template.
There is a predefined macro for grid rendering, that is defined in ` OroDataGridBundle::macros.html.twig` and can be imported
by the following call `{% import 'OroDataGridBundle::macros.html.twig' as dataGrid %}` .
Macro's name is `renderGrid`, it takes 2 arguments: grid name, route parameters(used for advanced query building).
So for displaying our grid we have to add following code to template:

``` twig
{% import 'OroDataGridBundle::macros.html.twig' as dataGrid %}
{% block content %}
     {{ dataGrid.renderGrid('acme-demo-datagrid') }}
{% endblock %}
```
**Note:** If your template extends the OroUIBundle:actions:index.html.twig template, macros will be already imported and you only have to set the gridName variable to get the grid rendered

#### Advanced Configuration

Actions, mass actions, toolbar, pagers, grid views and other functionality are explained on [advanced grid configuration](./advanced_grid_configuration.md) page or you can check [configuration reference](./configuration_reference.md).

## Extendability
#### Behavior Customization

In order to customize the datagrid (e.g. dynamically added columns, custom actions, add additional data, etc.), you can listen to one of the events dispatched in the datagrid component. 
More information on events, including their full list, is available in the [events documentation](./events.md).

#### Extending
The grid can be extended in several ways:

- create custom datasource if needed (e.g. already implemented SearchDatasource for working with search engine)
- create custom extension ([ref](./extensions.md))
- create some addons to already registered extensions (e.g. some specific backend formatter)
- change base datagrid or base acceptor class (they are passed to builder as DIC parameters)
