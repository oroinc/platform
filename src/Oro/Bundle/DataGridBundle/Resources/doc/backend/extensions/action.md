Action Extension
================

Overview:
---------

This extension is responsible for configuring actions for datagrid. Action types could be easily added by developers.
Configuration for actions should be placed under `actions` node.

Actions
-------

`type` is required option for action configuration.
Action access could be controlled by adding `acl_resource` node for each action (this parameter is optional).

### Ajax

Performs ajax call by given url.

``` yml
action_name:
    type: ajax
    link: PROPERTY_WITH_URL # required
```

### Delete

Performs DELETE ajax request by given url.

``` yml
action_name:
    type: delete
    link: PROPERTY_WITH_URL  # required
    confirmation: true|false # should confirmation window be shown
```

### Navigate

Performs redirect by given url.

``` yml
action_name:
    type: navigate
    link: PROPERTY_WITH_URL  # required
```

### Import

Performs import of an entity.

``` yml
action_name:
    type: import
    entity_class: 'Acme\Bundle\DemoBundle\Entity\TestEntity'
    importProcessor: 'acme_import_processor' # required
    importJob: 'acme_import_from_csv'
    options:
        refreshPageOnSuccess: false # refresh page after success
        importTitle: Custom Import Title
        datagridName: 'acme-entity-grid' # refresh datagrid after success
        routeOptions:
            param1: value1
```

### Export

Performs export of an entity.

``` yml
action_name:
    type: import
    entity_class: 'Acme\Bundle\DemoBundle\Entity\TestEntity'
    exportProcessor: 'acme_export_processor' # required
    exportJob: 'acme_export_to_csv'
    filePrefix: 'test-entity-prefix'
    options:
        routeOptions:
            param1: value1
```

Row click
----------
If you want to configure action that will executes on row click. You have to set `rowAction` param to true.


Control actions on record level and custom configuration
--------------------------------------------------------
To manage(show/hide) some actions by condition(dependent on row), developer should add action_configuration option to datagrid configuration. 
This option should contain array or closure. Closure should return an array of actions, that must be shown/hidden. 
Key of array should be an action name and value should be true(or array)/false value (show/hide respectively). 
This configuration will be passed to JavaScript component.
``` yml
# static configuration
action_configuration:
    action1: false # hidden
    action2: true # shown
    action3: {param1: 'value1'} # shown and pass {param1: 'value1'} to component
```

``` yml
# dynamic configuration
action_configuration: ['@acme.datagrid.action_configuration_provider', 'getActionConfiguration']
```

