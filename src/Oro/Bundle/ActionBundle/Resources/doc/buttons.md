Buttons
=======

Together with [Operations](./operations.md), ActionBundle provides a useful way for a developer to add your specific
 User Interface Buttons for some context matches that are common in the OroPlatform based applications.
Those are `entity` (FQCN with optional `id`), `routeName`, `grid` (the datagrid name), `referrer` (a URL), `group` (any named sort of a group).
 In other words - all that fit to a [ButtonSearchContext](../../Model/ButtonSearchContext.php) model parameters.
And then, a developer can implement its own `ButtonProviderExtension` by the interface and deliver a list of buttons
 to general button provider that places that buttons within a proper UI context.

To add your own ButtonProvider in system, you must implement the [ButtonProviderInterface](../../Model/ButtonProviderInterface.php) 
and register it as a service with a tag `oro_action.provider.button`. 
Then, when an application will meet a context, that corresponds to ActionBundle buttons it will ask for a list of matched buttons (ButtonInterface) in each registered (e.g. tagged) provider and deliver them to UI.
All that your provider should do - is to return an array of ButtonInterface implementations from `find` method.

For example, Operations are implemented in the same way and the button provider service looks like:
```YAML

    oro_ui.provider.button.extension.operation:
        class: 'Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension'
        arguments:
            - '@oro_action.operation_registry'
            - '@oro_action.helper.context'
            - '@oro_action.helper.applications'
        tags:
            - { name: oro.action.extension.button_provider, priority: 100 }

```
