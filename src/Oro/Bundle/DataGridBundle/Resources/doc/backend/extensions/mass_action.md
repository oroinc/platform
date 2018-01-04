Mass Action Extension
==================

Simplest mass action that works "out of box" with data grids is `delete`. To enable it add following in `datagrids.yml` of corresponding grid :
``` yml
datagrids:
    users-grid:
    ...
    actions:
        delete:
            type:          delete
            label:         oro.grid.action.delete
            link:          delete_link
            icon:          trash-o
            acl_resource:  oro_user_user_delete
```
After that you should see empty checkboxes and `thrash` icon in every grid row. By pressing this icon you can delete single current row. 
Button with `...` label will also appeared on right side of grid header. Click on it and you should see `Delete` mass action button. 
Just check every needed row manually or use checkbox in the header and press `Delete` to perform mass action. 

If you wish to disable some mass action you should specify:
``` yml
datagrids:
    users-grid:
        ...
        options:
            mass_actions:
                delete:
                    enabled: false
```

In case of more complicated mass types you should register your service with `oro_datagrid.extension.mass_action.type` tag:

``` yml
 oro_customer.datagrid.extension.mass_action.handler.custom:
     class: Oro\Bundle\CustomerBundle\Datagrid\Extension\MassAction\CustomActionHandler
     ...
 tags:
     - { name: oro_datagrid.extension.mass_action.type, type: disableusers }
```
Then add following configuration to `actions.yml`
``` yml
operations:
...
    user_disable:
        label: oro.user.action.disable.label
        acl_resource: oro_user_user_update
        entities:
            - Oro\Bundle\UserBundle\Entity\User
        routes:
            - oro_user_view
            - oro_user_index
        datagrids:
            - users-grid
        datagrid_options:
            mass_action:
                type: disableusers
                label: oro.customer.mass_actions.disable_customers.label
                handler: oro_customer.datagrid.mass_action.customers_enable_switch.handler.disable
                route: oro_datagrid_front_mass_action
                route_parameters: []
                icon: ban
                data_identifier: customerUser.id
                object_identifier: customerUser
                defaultMessages:
                    confirm_title: oro.customer.mass_actions.disable_customers.confirm_title
                    confirm_content: oro.customer.mass_actions.disable_customers.confirm_content
                    confirm_ok: oro.customer.mass_actions.disable_customers.confirm_ok
                allowedRequestTypes: [POST, DELETE]
                requestType: [POST]
```

**Note:**
 - `allowedRequestTypes` intended to use for mass action request server side validation. If it's not specified, request compared to `GET` method.
 - `requestType` intended to use for mass action to override default HTTP request type `GET` to one from allowed types. If it's not specified will be `GET` type.

How to configure operations described in [Operations](../../../../../ActionBundle/Resources/doc/operations.md) article.

