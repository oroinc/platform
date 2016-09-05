# Board
## How to enable board appearance on a grid
To add board appearance on a grid, do the following actions:

- Go to the datagrid yml
- Add the following lines into the datagrid configuration
``` yml
datagrid:
    {grid-uid}:
        # <grid configuration> goes here
        appearances:
            board:
                {board-uid}: #unique board id
                    label: Board Label
                    group_by:
                        property: option_set_field
                        order_by:
                            priority: ASC
                    card_view: demobundle/js/app/views/board/your-entity-card-view
```

## Datagrid configuration details

 - label (Optional)

 A label to be shown in appearance switcher.

 - icon (Optional)

 Icon class to be shown in appearance switcher.

 - group_by (Required)

 Configuration array for column groupping property.
``` yml
    group_by:
        property: status #required, enum property to be used for board columns
        order_by: #optional, used to define a property's field which should be used for columns sort order.
            priority: ASC
```

 - default_column (Optional)

 Specifies a column id to use for showing entities which don't have any value set for group_by `property`. By default, first column will be used.

 - plugin (Optional)

 Specifies the plugin realization. Default `orodatagrid/js/app/plugins/grid-component/board-appearance-plugin`

 - board_vew (Optional)

 Specifies the view for kanban board. Default `orodatagrid/js/app/views/board/board-view`

 - card_view (Required)

 Specifies the view for kanban card.

 - column_header_view (Optional)

 Specifies the view for board column header. Default `orodatagrid/js/app/views/board/column-header-view`

 - column_view (Optional)

 Specifies the view for board column. Default `orodatagrid/js/app/views/board/column-view`

- acl_resource (Optional)

 Acl resource to check for allowing board items transitions. If no permission granted, board will be in readonly mode.

- processor (Optional)

 Specified the name of board processor. `default` processor is used by default.

- default_transition (Optional)

 Section to specify configuration for transition, e.g. update property when card after drag&drop from one column to another

``` yml
    default_transition:
        class: #class to be used for transition
        params: #additional params to pass to transition
            key: value
        save_api_accessor: #Describes the way to send update request. Please see [documentation for `oroui/js/tools/api-accessor`](../../../../../UIBundle/Resources/doc/reference/client-side/api-accessor.md)

```







