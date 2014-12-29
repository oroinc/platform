Scope
=====

Overview
--------

Scopes are intended to resolve conflicts on UI when you have more than one grid with same name on the page.
Each grid may have it's own scope and not affect other grid with same name.

Access grid scope
-----------------

Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface contains method to get scope (getScope).

It's also possible to specify scope of the grid in configuration, there is an option "scope" for this purpose:

``` yaml
datagrid:
    acme-demo-grid:
        scope: demo-scope
        # ...
```

This scope value will be used by default, but if grid is rendered on UI with different scope, it will be overridden for
this specific grid instance.


Specify scope in the view
-------------------------

More often you will need to specify scope name in your views.
Twig function is available to build grid name with scope (oro_datagrid_build_fullname), an example of usage:

``` twig
{% set fullname = oro_datagrid_build_fullname('acme-demo-datagrid', 'some-scope') %}
```

For example you want to render multiple grids of orders of customers:

``` twig
{% for (customer in customers) %}
    {{ dataGrid.renderGrid(
        oro_datagrid_build_fullname('acme-customer-order-grid', customer.id),
        {id: customer.id}
    ) }}
{% endfor %}
```

Each grid will be rendered within it's unique scope and not conflict with other grids.


Name strategy
-------------

By default class that is responsible for parsing grid name and scope from string passed client is
"Oro\Bundle\DataGridBundle\Datagrid\NameStrategy" (service name is "oro_datagrid.datagrid.name_strategy").

Grid manager ("oro_datagrid.datagrid.manager") and twig functions may handle grid names that contain scope, they
will delegate resolving of grid name and grid scope to name strategy.

In other places when you are dealing with grid name it's assumed that it not contains scope.

Correct grid full name with scope should match this pattern: /([\w-]+\):([\w-]+)/
Where first group is pure grid name and second group is scope, for example: "acme-demo-datagrid:some-scope".
