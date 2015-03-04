Layout context
==============

Basically, as any other context, the **layout context** is an object that is responsible for transferring configuration
to different components of the **layout** (such as extensions, block types etc).

The context should be instantiated directly in the code that triggers layout building mechanism.

```php
$layoutContext = new LayoutContext();
$layoutBuilder = $layoutManager->getLayoutBuilder();
$layoutBuilder->getLayout($layoutContext);
```

Context configurators
---------------------

It might be required to configure the context based on current application state, client setting or just define 
default values and so on. In order to prevent copy paste of boilerplate code **context configurators** have been introduced.
Each configurator should implement `\Oro\Component\Layout\ContextConfiguratorInterface` and be registered as a service 
in DI container with the tag `layout.context_configurator`. 

For debugging purposes `oro:layout:debug --context` command has been added, it allows to see how the context data-resolver will
be configured by **context configurators**.
