Column Manager
==============

Column Manager allows to:
- show/hide a column
- change the order of columns
- save columns state in [Grid View](./extensions/grid_views.md)

Column Manager operates with columns' attributes:
- `renderable` show/hide the column (if is not defined the column is shown)
- `order` is used to sort columns in a row
- `required` if `true` the column can not be hidden (but can be ordered)
- `manageable` if `false` the column does not appear in Column Manage (generally is used for system columns such as `actions` or `selectRow`)
