Datagrid Settings Manager
==============

Datagrid Settings allows to:
- show/hide a column or filters
- change the order of columns
- save columns state in [Grid View](./extensions/grid_views.md)

Datagrid Settings operates with columns' attributes:
- `renderable` show/hide the column/filters (if is not defined the column is shown)
- `order` is used to sort only columns in a row
- `required` if `true` the column/filters can not be hidden (but can be ordered)
- `manageable` if `false` the column does not appear in Datagrid Settings (generally is used for system columns such as `actions` or `selectRow`)

There's the option that allows to turn off Datagrid Settings over `datagrids.yml` configuration:

```yaml
datagrids:
    my-grid:
        ...
        options:
            toolbarOptions:
                datagridSettingsManager: false

```
