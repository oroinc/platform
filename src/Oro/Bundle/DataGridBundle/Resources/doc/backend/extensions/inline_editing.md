# Inline editing
## How to enable inline editing on a grid
To enable inline editing on a grid, do the following actions:

- Go to the datagrid yml
- Add the following lines into the datagrid configuration
``` yml
datagrid:
    {grid-uid}:
        # <grid configuration> goes here
        inline_editing:
            enable: true
        save_api_accessor:
            http_method: PATCH
            route: orocrm_account_update
```
- Open the corresponding page, all the columns for which the frontend type supports inline editing will become editable

## Datagrid configuration details
``` yml
datagrid:
    {grid-uid}:
        inline_editing:
            enable: true
            behaviour: enable_all
            plugin: orodatagrid/js/app/plugins/grid/inline-editing-plugin
            default_editors: orodatagrid/js/default-editors
            cell_editor:
                component: orodatagrid/js/app/components/cell-popup-editor-component
                component_options:
                    {key}: {value}
            save_api_accessor:
                # api accessor options
                {key}: {value}
```
Option name              | Default value | Description
:------------------------|:--------------|:-----------
enable    | false        | Enables inline editing on the grid. By default is enabled for all cells that have frontend type that supports inline editing
behaviour | enable_all   | Specifies the way to enable the inline editing. Possible values: `enable_all` - (default). this will enable inline editing where possible. `enable_selected` - disable by default, enable only on configured cells
plugin    | orodatagrid/js/app/plugins/grid/inline-editing-plugin | Specifies the plugin realization
default_editors | orodatagrid/js/default-editors | Specifies default editors for front-end types
cell_editor | {component: 'orodatagrid/js/app/components/cell-popup-editor-component'} | Specifies default cell_editor_component and their options
save_api_accessor | {class: 'oroui/js/tools/api-accessor'} | Required. Describes the way to send update request. Please see [documentation for `oroui/js/tools/api-accessor`](../../../../../UIBundle/Resources/doc/reference/client-side/api-accessor.md)

### Sample usage of the save_api_accessor with full options provided
``` yml
save_api_accessor:
    route: orocrm_opportunity_task_update # for example this route uses following mask
        # to generate url /api/opportunity/{opportunity_id}/tasks/{id}
    http_method: POST
    headers:
        Api-Secret: ANS2DFN33KASD4F6OEV7M8
    default_route_parameters:
        opportunity_id: 23
    action: patch
    query_parameter_names: [action]
```

Result of the combined options:

`/api/opportunity/23/tasks/{id}?action=patch`

Please note that `{id}` will be taken from the current row in the grid

## Column configuration options
``` yml
datagrid:
    {grid-uid}:
        # <grid configuration> goes here
        columns:
            {column-name}:
                inline_editing:
                    enable: true
                    save_api_accessor:
                        # see main save_api_accessor, additonally supports field_name option
                        # which allows to override field name that sent to server
                        # {<field_name>: <new_value>}
                    editor:
                        component: my-bundle/js/app/components/cell-editor-component
                        component_options:
                            {key}: {value}
                        view: my-bundle/js/app/views/my-cell-editor-view
                        view_options:
                            {key}: {value}
```

Options name | Default value | Description
:------------|:--------------|:-----------
enable | | Marks or unmarks this column as editable. The behaviour depends on main inline_editing.behaviour: `enable_all` - false will disable editing this cell. `enable_selected` - true will enable editing this cell.
save_api_accessor | | Allows to override default api accessor for the whole grid. Please see [documentation for `oroui/js/tools/api-accessor`](../../../../../UIBundle/Resources/doc/reference/client-side/api-accessor.md) for details
editor.component | | Allows to override component used to display view and specified in `datagrid.{grid-uid}.inline_editing.cell_editor.component`
editor.component_options | {} | Specifies options to pass into the cell editor component
editor.view | | Defines view that used to render cell-editor. By default, this view is selected using `datagrid.{grid-uid}.inline_editing.default_editors` file.
editor.view_options | {} | Specifies options to pass into the cell editor view

## Mapping of the default frontend type and editor

Frontend type | Editor view
:-------------|:-----------------------------------------------------------------
string        | [text-editor-view](../../frontend/editor/text-editor-view.md)
datetime      | [datetime-editor-view](../../frontend/editor/datetime-editor-view.md)
date          | [date-editor-view](../../frontend/editor/date-editor-view.md)
currency      | [number-editor-view](../../frontend/editor/number-editor-view.md)
number        | [number-editor-view](../../frontend/editor/number-editor-view.md)
integer       | [number-editor-view](../../frontend/editor/number-editor-view.md)
decimal       | [number-editor-view](../../frontend/editor/number-editor-view.md)
percent       | [percent-editor-view](../../frontend/editor/percent-editor-view.md)
select        | [select-editor-view](../../frontend/editor/select-editor-view.md)

Taken from [default-editors.md](../default-editors.md)

## Supported editors

Editor                                                                      | Description
:---------------------------------------------------------------------------|:-----------------------------------------------------
[text-editor-view](../../frontend/editor/text-editor-view.md)                  | Editing text cells
[number-editor-view](../../frontend/editor/number-editor-view.md)              | Editing number/integer/decimal/currency cells
[percent-editor-view](../../frontend/editor/percent-editor-view.md)            | Editing percent  cells
[date-editor-view](../../frontend/editor/date-editor-view.md)                  | Editing date cells
[datetime-editor-view](../../frontend/editor/datetime-editor-view.md)          | Editing datetime cells
[select-editor-view](../../frontend/editor/select-editor-view.md)              | Editing select cells (predefined choices)
[related-id-select-editor-view](../../frontend/editor/related-id-select-editor-view.md)   | Editing cells which already contain label (and no value) using predefined choices
[related-id-relation-editor-view](../../frontend/editor/related-id-relation-editor-view.md) | Editing cells which already contain label (and no value) using search api

## Supported search API's for `related-id-relation-editor-view`

Please find the list of supported search APIs [here](../../frontend/search-apis.md)
