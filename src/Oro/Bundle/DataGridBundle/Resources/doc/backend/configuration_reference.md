Configuration References
===========================

The description of options that you can pass in the datagrid configuration is available below.

## Setting Options
To set datagrid options, define them under the datagrid_name.options path.
``` yaml
datagrids:
    acme-demo-datagrid:
        options:
```
   
## Options List   
            
### base_datagrid_class
```yaml
base_datagrid_class: Acme\Bundle\AcmeBundle\Grid\CustomEntityDatagrid
```
This option can be used to change the default datagrid implementation to a custom class.
            
### entity_pagination:

- values: true|false
- default: true

Enables pagination UI for a collection of entities when these entities are part of a data set of a datagrid.
Please take a look at [OroEntityPaginationBundle](./../../../../EntityPaginationBundle/README.md) for more information.

### export            

- values: true|false
- default: false

When set to `true`, grid export button will be shown. 
More information of export configuration is available in the [exportExtension](./extensions/export.md) topic.

### frontend

- values: true|false
- default: false

Set the flag to 'true' to display the datagrid on the frontend. If set to 'false', the datagrid will be hidden.

### mass_actions

Detailed information on the mass action extension is available in the [Mass action extension](./extensions/mass_action.md) topic.

### toolbarOptions

Detailed information on toolbars is available in the [toolbarExtension](./extensions/toolbar.md) and [pagerExtension](./extensions/pager.md) topics.

### requireJSModules

```yaml
requireJSModules:
  - your/builder/amd/module/name
```

Adds given JS files to the datagrid. JS files should have the 'init' method which will be called when the grid builder finishes building the grid.

### routerEnabled

- values: true|false
- default: true

When set to `false` datagrid will not keep its state (e.g. filtering and/or sorting parameters) in the URL.

### rowSelection

``` yaml
rowSelection:
    dataField: id
    columnName: hasContact
    selectors:
        included: '#appendContacts'
        excluded: '#removeContacts'
```
More information on row selection and an example of its usage are available in the [Advanced grid configuration (How to's)](./advanced_grid_configuration.md#solution-1) article.

