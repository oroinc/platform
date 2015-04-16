Grid Views Extension
==================

**@TODO create doc**

Configuration
-------------

### datagrid.yml

``` yml
    # ...
     options:
            gridViews:
                allLabel: Custom label # label of default view (default: "All {Entity name}")
```

### dashboard.yml

``` yml
    accounts_grid:
        label: Accounts grid
        route: oro_dashboard_grid # using this route configuration for selection of grid view will be added automatically
        route_parameters:
            widget: accounts_grid
            gridName: accounts-grid
            renderParams:
                routerEnabled: true # enable storing grid state in url
                enableFilters: true # enable showing filters (default: false)
                enableViews: true # enable showing views (default: enableFilters)
```
