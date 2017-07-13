# Board
## How to enable board appearance on a grid
To add board appearance on a grid, do the following actions:

- Go to the datagrid yml
- Add the following lines into the datagrid configuration
``` yml
datagrids:
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

*Note:* board appearance uses `save_api_accessor` internally, so please check that at least one of the following options is configured properly:
 * `save_api_accessor` for column transition option (check [documentation for `oroui/js/tools/api-accessor`](../../../../../UIBundle/Resources/doc/reference/client-side/api-accessor.md))
 * `save_api_accessor` for default transition (check `default_transition` configuration option below)
 * `save_api_accessor` for inline editing (check this [article](./inline_editing.md#sample-usage-of-the-save_api_accessor-with-full-options-provided))

## Datagrid configuration details

 - label (Optional)

 A label to be shown in the appearance switcher.

 - icon (Optional)

 The icon class to be shown in the appearance switcher.

 - group_by (Required)

 Configuration array for column grouping property.
``` yml
    group_by:
        property: status #required, enum property to be used for board columns
        order_by: #optional, used to define a property's field which should be used for columns sort order.
            priority: ASC
```

 - default_column (Optional)

 Specifies a column ID to show entities which do not have any value set for group_by `property`. By default, the first column will be used.

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

 Enabled Acl resource checks whether board items transitions are allowed. If no permission is granted to a user, they see the board in read only mode.

- processor (Optional)

 Specified the name of the board processor. `default` processor is used by default.

- default_transition (Optional)

 Section to specify configuration for the transition, e.g. update property when cards are dragged and dropped from one column to another.

``` yml
    default_transition:
        class: #class to be used for transition
        params: #additional params to pass to transition
            key: value
        save_api_accessor: #Describes the way to send update request. Please see [documentation for `oroui/js/tools/api-accessor`](../../../../../UIBundle/Resources/doc/reference/client-side/api-accessor.md)

```







