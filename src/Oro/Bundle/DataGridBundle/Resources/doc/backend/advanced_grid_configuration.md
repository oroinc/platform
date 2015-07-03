Advanced grid configuration
===========================

This page contains basic examples of advanced datagrid configuration. More detailed explanation for each extension could be found [here](./extensions.md)


## Problems and solutions

#### Problem:
Datagrid should show data dependent on some param
For example "_grid should show users for group that currently editing_"
#### Solution:
Macros that renders datagrid could retrieve parameters that will be used for generating URL for data retrieving.

Example:

``` twig
[dataGrid.renderGrid(gridName, {groupId: entityId})]
```

This param will be passed to datagrid parameter bag and will be bind to datasource query in listener
of "oro_datagrid.datagrid.build.after" event automatically if you will specify "bind_parameters" option in datasource
configuration:

``` yml
datagrid:
    acme-demo-grid:
        source:
            type: orm
            query:
                select:
                    - u
                from:
                    { table: AcmeDemoBundle:User, alias:u }
            where:
                and:
                    - u.group = :groupId
            bind_parameters:
                - groupId
```
[More about parameters binding](./parameter_binding.md).

#### Problem:
Let's take previous problem, but in additional we need to fill some form field dependent on grid state.
For example "_grid should show users for group that currently editing and user should be able to add/remove users from group_"
#### Solution:
For solving this problem we have to modify query. We'll add additional field that will show value of "assigned state".
``` yml
datagrid:
    acme-demo-grid:
        source:
            type: orm
            query:
                select:
                    - u.id
                    - u.username
                    - >
                        (CASE WHEN (:groupId IS NOT NULL) THEN
                              CASE WHEN (:groupId
                                     MEMBER OF u.groups OR u.id IN (:data_in)) AND u.id NOT IN (:data_not_in)
                              THEN true ELSE false END
                         ELSE
                              CASE WHEN u.id IN (:data_in) AND u.id NOT IN (:data_not_in)
                              THEN true ELSE false END
                         END) as isAssigned
                from:
                    { table: AcmeDemoBundle:User, alias:u }
            bind_parameters:
                - groupId
        columns:
            isAssigned: # column has name correspond to data_name
                label: Assigned
                frontend_type: boolean
                editable: true # put cell in editable mod
            username:
                label: Username
        properties:
            id: ~  # Identifier property must be passed to frontend
```

When this done we have to create form fields that wil contain assigned/removed user ids and process it on backend
For example fields are:
``` twig
    form_widget(form.appendUsers, {'id': 'groupAppendUsers'}),
    form_widget(form.removeUsers, {'id': 'groupRemoveUsers'}),

```

Last step: need to set "rowSelection" option, it will add behavior of selecting rows on frontend and handle binding
of "data_in" and "data_not_in" parameters to datasource:
``` yml
datagrid:
    acme-demo-grid:
        ... # previous configuration
        options:
            entityHint: account
            rowSelection:
                dataField: id
                columnName: isAssigned    # frontend column name
                selectors:
                    included: '#groupAppendUsers'  # field selectors
                    excluded: '#groupRemoveUsers'
```

#### Problem:
*Let's take previous problem, when we need to fill some form field dependent on grid state.
For example "_grid should show users for group that currently editing and user should be able to select some parameter from dropwown for users in this group_"*
#### Solution:
For solving this problem we have to create form field that will contain changeset of edited user fields and process it on backend
For example fields are:
``` twig
    form_widget(form.changeset, {'id': 'changeset'}),
```

Next step: modify query. We'll add additional field `enabled` that user will be able to change.
``` yml
datagrid:
    acme-demo-grid:
        source:
            type: orm
            query:
                select:
                    - u.id
                    - u.username
                    - CASE WHEN u.enabled = true THEN 'enabled' ELSE 'disabled' END as enabled
                from:
                    { table: AcmeDemoBundle:User, alias:u }
            bind_parameters:
                - groupId
        options:
            entityHint: user
        properties:
            id: ~
        columns:
            username:
                label: oro.user.username.label
            enabled:
                label: oro.user.enabled.label
                frontend_type: select
                editable: true
                choices:
                   enabled: Active
                   disabled: Inactive
```

Similarly Symfony2 ``choice Field Type`` approach editable cell may be rendered as one of several different HTML fields, depending on the ``expanded`` and ``multiple`` options.
Currently supported ``select tag``, ``select tag (with multiple attribute)`` and ``radio buttons``.

Example for radio buttons:

``` yml
datagrid:
    acme-demo-grid:
        ... # some configuration
        columns:
            username:
                label: oro.user.username.label
            enabled:
                label: oro.user.enabled.label
                frontend_type: select
                editable: true
                expanded: true
                multiple: false
                choices:
                   enabled: Active
                   disabled: Inactive
```
By default ``expanded`` and ``multiple`` are ``false`` and their presence in config may be omitted.

Last step: need to set "cellSelection" option, it will add behavior of selecting rows on frontend:
``` yml
datagrid:
    acme-demo-grid:
        ... # previous configuration
        options:
            cellSelection:
                dataField: id
                columnName:
                    - enabled
                selector: '#changeset'
```

#### Problem:
*Let's take previous problem, but in additional we need to fill selector in addiction to enum values.*
#### Solution:
For solving this problem we have to use ``@oro_entity_extend.enum_value_provider->getEnumChoicesByCode('enum_code')``
instead of choice array using
```yml
    choices:
       enabled: Active
       disabled: Inactive
```

Example:
``` yml
datagrid:
    acme-demo-grid:
        ... # some configuration
        columns:
            username:
                label: oro.user.username.label
            enabled:
                label: oro.user.enabled.label
                frontend_type: select
                editable: true
                choices: @oro_entity_extend.enum_value_provider->getEnumChoicesByCode('user_status')
```

#### Problem:
*I'm developing some extension for grid, how can I add my frontend builder (some class that should show my widget) ?*
#### Solution:
Any builders could be passed under gridconfig[options][requireJSModules] node. Your builder should have method `init`, it will be called when grid-builder finish building grid.

Example:
``` yml
datagrid:
    acme-demo-grid:
        ... # some configuration
        options:
            requireJSModules:
              - your/builder/amd/module/name
```

#### Problem:
*I'm developing grid that should be shown in modal window, so I don't need "grid state url"*
#### Solution:
Grid states processed using Backbone.Router, and it could be easily disabled in configuration by setting `routerEnabled` option to false.

Example:
``` yml
datagrid:
    acme-demo-grid:
        ... # some configuration
        options:
            routerEnabled: false
```

#### Problem:
*I'm developing grid that should not be under ACL control*
#### Solution:
- set option 'skip_acl_check' to TRUE

Example:
``` yml
datagrid:
    acme-demo-grid:
        ... # some configuration
        options:
            skip_acl_check: true
```

#### Problem:
*I want to implement some custom security verification/logic without any default acl, even if some "acl_resource" have been defined *
*e.g. i'm extending some existing grid but with custom acl logic*
#### Solution:
- configure grid (set option 'skip_acl_check' to TRUE)
``` yml
datagrid:
    acme-demo-grid:
        ... # some configuration
        options:
            skip_acl_check: true
```
- declare own grid listener
```
my_bundle.event_listener.my_grid_listener:
        class: %my_grid_listener.class%
        arguments: ~
        tags:
            - { name: kernel.event_listener, event: oro_datagrid.datagrid.build.before.my-grid-name, method: onBuildBefore }
```
- last step is implementing grid listener
- as an example see:
    - Oro/Bundle/UserBundle/Resources/config/datagrid.yml (owner-users-select-grid)
    - Oro/Bundle/UserBundle/EventListener/OwnerUserGridListener.php (service name: "oro_user.event_listener.owner_user_grid_listener")
