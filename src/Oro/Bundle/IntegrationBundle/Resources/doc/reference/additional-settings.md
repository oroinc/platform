#Additional setting for integration

Integration entity contains two additional serializable fields that allows developers to store platform specific
settings there. Those fields are **synchronization settings** and **mapping settings**. They could be retrieved using
getters `getSynchronizationSettings()` and `getMappingSettings()` respectively.

_Note: doctrine2 will not update object type fields if values were changed by reference, due to this getters return **clonned** objects_

In order to allow put configuration fields into integration creation form `integration_settings.yml` config file type was added.
Root node should be `oro_integration` and form configuration should be placed under `form` node.

**Example**

```yaml

    oro_integration:
        form:
            synchronization_settings: # form name (now synchronization_settings and mapping_settings are available)
                isTwoWaySyncEnabled:  # field name
                    type: checkbox    # form field type
                    options:          # form options
                        label:    oro.integration.integration.is_two_way_sync_enabled.label
                        required: false
                    applicable: [some_integration_type]  # on which integration types this setting should be shown
```

This configuration will be resolved by `SystemAwareResolver` so any node could contains DI service calls or constants.
For example if you wants to bring dynamic behavior to `applicable` node you can put service call there, `$channelType$`
will be in resolver context. For example string `applicable: @some.service->methodOfService($channelType$)` will be invoke
`methodOfService` function in class that registered in DI as `some.service`.
