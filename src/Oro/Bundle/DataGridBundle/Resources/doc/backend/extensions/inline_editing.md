# Inline editing
## How to enable inline editing on grid:
To enable inline editing on grid you must do following actions:

- Go into datagrid yml
- Add into datagrid configuration following lines
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
- Open corresponding page, all columns that have supported frontend type will become editable

## Datagrid configuration details:
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
                # api aceesor options
                {key}: {value}
```
Option name              | Default value | Description
-------------------------|---------------|------------
enable    | false         | Enables inline editing on grid. By default on all cells what have frontend type that support inline editing
behaviour | enable_all    | Specifies a way how inline editing will be enabled. Possible values: `enable_all` - (default). this will enable inline editing where possible. `enable_selected` - disable by default, enable only on configured cells
plugin    | orodatagrid/js/app/plugins/grid/inline-editing-plugin | Specifies plugin realization
default_editors | orodatagrid/js/default-editors | Specifies default editors for front-end types
cell_editor | {component: 'orodatagrid/js/app/components/cell-popup-editor-component'} | Specifies default cell_editor_component and their options
save_api_accessor | {class: 'oroui/js/tools/api-accessor'} | Required. Describes how update request will be sent. Please overview [documentation for `oroui/js/tools/api-accessor`](../../../../../UIBundle/Resources/doc/reference/client-side/api-accessor.md)

inline_editing.save_api_accessor.route specifies route
inline_editing.save_api_accessor.class specifies class that realizes this accessor, by default
oroui/js/tools/api-accessor
	inline_editing.save_api_accessor.http_method specifies http_method to access this route
	inline_editing.save_api_accessor.form_name wraps request body into form_name, so request will look like
					{<form_name>:{<field_name>: <new_value>}}
inline_editing.save_api_accessor.headers allows to provide additional http headers
inline_editing.save_api_accessor.default_route_parameters provides default parameters values for
route creation, this defaults will be merged with row model data to get url
inline_editing.save_api_accessor.query_parameter_names array of parameter names to put into query
string (e.g. ?<parameter-name>=<value>&<parameter-name>=<value>). (Actually
FOSRestBundle doesn’t provides them for client usage, so it is required to specify list of available query parameters)

Sample usage of save_api accessor with full options provided:
      save_api_accessor:
            route: orocrm_opportunity_task_update # route uses following mask
 					# to generate url /api/opportunity/{opportunity_id}/tasks/{id}
            http_method: POST
		headers:
    Api-Secret: ANS2DFN33KASD4F6OEV7M8
default_route_parameters:
    opportunity_id: 23
    action: patch
query_parameter_names: [action]
 Result of combining options:
/api/opportunity/23/tasks/{id}?action=patch
            Please note that {id} will be taken from current row in grid

## Column configuration options:
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
                        # {<form_name>:{<field_name>: <new_value>}}
                    editor:
                        component: my-bundle/js/app/components/cell-editor-component
                        component_options:
                            {key}: {value}
                        view: my-bundle/js/app/views/my-cell-editor-view
                        view_options:
                            {key}: {value}
```

Options name | Default value | Description
-------------|---------------|------------
enable | | Marks or unmarks this column as editable, behaviour depends on main inline_editing.behaviour: `enable_all` - false will disable editing this cell. `enable_selected` - true will enable editing this cell.
save_api_accessor | | Allows to override default api accessor for all grid. Please overview [documentation for `oroui/js/tools/api-accessor`](../../../../../UIBundle/Resources/doc/reference/client-side/api-accessor.md) for details
editor.component | | Allows to override component used to display view and specified in `datagrid.{grid-uid}.inline_editing.cell_editor.component`
editor.component_options | {} | specifies options to pass into cell editor component
editor.view | | defines view that used to render cell-editor. By default this view is selected using `datagrid.{grid-uid}.inline_editing.default_editors` file.
editor.view_options | {} | specifies options to pass into cell editor view
