Grid layout configuring
==============

### Basic settings for layout grid:

1. In `layouts/some_theme/layout.yml` specify:
```yaml
layout:
    imports:
        -
            id: datagrid
            root: __root

    actions:
        - '@setOption':
            id: __datagrid
            optionName: grid_name
            optionValue: frontend-some-grid
```

2. In `/config/oro/datagrids.yml` should be defined:

```yaml
datagrids:
    frontend-some-grid:
...
```

As we see in `layout.yml`, we need to extend generic layout block first. Later defined in `OroDataGridBundle` (`imports` directive used). Also we should to specify `optionName` with `grid_name` and `optionValue` with grid identifier value defined in `datagrids.yml`. 

If we open generic layout block for `base` theme (`base/imports/datagrid/layout.yml`) we could see other related with datagrid block: `datagrid_toolbar`:
```yaml
layout:
    imports:
         -
             id: datagrid_toolbar
             root: __root

    actions:
        - '@addTree':
            items:
                __datagrid:
                    blockType: datagrid
            tree:
                __root:
                    __datagrid: ~
```

This block is responsible for rendering grid toolbar, and it consists of different blocks like page_size, pagination, sorting, etc. which also customisable using layouts.

### Layout grid configuring:

Through layout directives like `visible` , `@move`, `@setOption`, etc. we can configure grid settings and params on layout level.

For example, we can set block visibility based on some logic using Symfony expression language:

```yaml
layout:
    actions:
        - '@add':
            id: products
            parentId: page_content
            blockType: datagrid
            options:
                grid_name: products-grid
                visible: '=data["feature"].isFeatureEnabled("product_feature")'
```

In `DataGridBundle/Layout/Block/Type/DatagridType.php` defined additional parameters used for grid rendering:

```php
    'grid_parameters' => [],
    'grid_render_parameters' => [],
    'split_to_cells' => false,
```
Using `split_to_cells` parameter we can manipulate grid layout on more detailed level - table cells. How to use this param described in [Grid customization through 'split to cells' option](./grid_customization.md)
Other params defined in Twig macros `renderGrid` (`DataGridBundle/Resources/views/macros.html.twig`):

- `grid_parameters` - parameters need to be passed to grid request
- `grid_render_parameters` - render parameters need to be set for grid rendering

Suppose we need to change some parameters that affects grid layouts on `Account > Quotes` frontend page.

Using [Layout Developer Toolbar](../../../../LayoutBundle/Resources/doc/debug_information.md) in developer mode we can quickly find out grid layout identifiers: `quotes_datagrid` and `quotes_datagrid_toolbar`. On `Build Block` view we can see `grid_name` parameter: `frontend-quotes-grid`.

Lets change some options for this grid layout.

In `SaleBundle/Resources/views/layouts/default/imports/oro_sale_quote_grid/layout.yml` we can specify css class
that will be used for grid rendering:

```yaml
    - '@setOption':
        id: __datagrid
        optionName: grid_render_parameters
        optionValue:
            cssClass: 'some-css-class'
```

If we inspect HTML page with grid we see that class atrribute was added to div element: `class="some-css-class"`

In order to pass some extra param to grid request lets specify for example `web_catalog_id` context param:

```yaml
    - '@setOption':
        id: __datagrid
        optionName: grid_parameters
        optionValue:
            web_catalog_id: '=context["web_catalog_id"]'
```

If we make some actions with grid like sorting, we see that additional request attribute `web_catalog_id` was added:

```
...
frontend-quotes-grid[originalRoute]:oro_sale_quote_frontend_index
frontend-quotes-grid[web_catalog_id]:1
appearanceType:grid
frontend-quotes-grid[_pager][_page]:1
frontend-quotes-grid[_pager][_per_page]:25
...
```

Suppose we want to modify datagrid toolbar. Lets hide block with page size:
```
    - '@setOption':
        id: __datagrid_toolbar_page_size
        optionName: visible
        optionValue: false
```
After refresh page `Page size` will be hidden.
