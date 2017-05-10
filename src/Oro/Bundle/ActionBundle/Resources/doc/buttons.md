Buttons
=======

Together with [Operations](./operations.md), ActionBundle provides a useful way for a developer to add your specific
 User Interface Buttons for some context matches that are common in the OroPlatform based applications.
Those are `entity` (FQCN with optional `id`), `routeName`, `grid` (the datagrid name), `referrer` (a URL), `group` (any named sort of a group).
 In other words - all that fit to a [ButtonSearchContext](../../Button/ButtonSearchContext.php) model parameters.
And then, a developer can implement its own `ButtonProviderExtension` by [the interface](../../Extension/ButtonProviderExtensionInterface.php) and deliver a list of buttons
 to general button provider that places found buttons within a proper UI context.

To add your own ButtonProvider in system, you must implement the [ButtonProviderExtensionInterface](../../Extension/ButtonProviderExtensionInterface.php) 
and register it as a service with a tag `oro_action.provider.button`. 

For example, Operations are implemented in the same way and the button provider service looks like:
```YAML

    oro_action.provider.button.extension.operation:
        class: 'Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension'
        arguments: 
            - #dependencies...
        tags:
            - { name: oro.action.extension.button_provider, priority: 100 } #<- register/inject extension via tag

```

Afterwards, when an application will meet a context that corresponds to ActionBundle buttons it will ask for a list of matched buttons (ButtonInterface) in each registered (e.g. tagged) provider and deliver them to UI.
All that your provider should do - is to return an array of ButtonInterface implementations from `find` method.
Additionally, if context of button search was not fully defined at `find` there could be called `ButtonProviderExtensionInterface::isAvailable()` method, as a filtering mechanism.

A [`ButtonInterface`](../../Button/ButtonInterface.php) implementation should gather all required data for rendering. Mostly from `ButtonSearchContext`.
You might control button presentation (view) through the button template `ButtonInterface::getTemplate()` and its data (via `ButtonInterface::getTemplateData()`). 

#### Button Match Event
For logic injection purposes you can rely on event `oro_action.button_provider.on_buttons_matched` 
witch is dispatched with general `@event_dispatcher` service and emits currently matched buttons collection wrapped 
with [`OnButtonsMatched`](../../Provider/Event/OnButtonsMatched.php) event object.
