# Buttons

Together with [Operations](./operations.md), ActionBundle provides a useful way for a developer to add specific User Interface Buttons for some context matches that are common in the OroPlatform based applications.
Mainly, those are `entity` (FQCN with optional `id`), `routeName`, `grid` (the datagrid name), `referrer` (a URL), `group` (any named type of a group), and everything that matches the [ButtonSearchContext](../../Button/ButtonSearchContext.php) model parameters.
Then, a developer can implement any `ButtonProviderExtension` by [the interface](../../Extension/ButtonProviderExtensionInterface.php) and send a list of buttons
 to general button provider that allocates found buttons within a proper UI context.

To add a new ButtonProvider to system, first, implement the [ButtonProviderExtensionInterface](../../Extension/ButtonProviderExtensionInterface.php) and then register it as a service with the `oro_action.provider.button` tag. 

For example, if Operations are implemented in the above mentioned way, the button provider service looks as follows:
```YAML

    oro_action.provider.button.extension.operation:
        class: 'Oro\Bundle\ActionBundle\Extension\OperationButtonProviderExtension'
        arguments: 
            - #dependencies...
        tags:
            - { name: oro.action.extension.button_provider, priority: 100 } #<- register/inject extension via tag

```

Afterwards, when an application meets a context that corresponds to ActionBundle buttons, it requests a list of matching buttons (ButtonInterface) in each registered (e.g. tagged) provider and delivers them to UI.
The provider returns an array of ButtonInterface implementations from the `find` method.
Additionally, if the button search context is not fully defined at `find`, the `ButtonProviderExtensionInterface::isAvailable()` method is called as a filtering mechanism.

The [`ButtonInterface`](../../Button/ButtonInterface.php) implementation collects all the data required for rendering, mostly from `ButtonSearchContext`.
You can control the button representation (view) through the `ButtonInterface::getTemplate()` template and its data (via `ButtonInterface::getTemplateData()`). 

#### Button Match Event

You can rely on the `oro_action.button_provider.on_buttons_matched` event which is dispatched with general `@event_dispatcher` service and emits currently matched buttons collection connected to the [`OnButtonsMatched`](../../Provider/Event/OnButtonsMatched.php) event object.
