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

Accessing context
-----------------

There are few ways how context could be accessed. Most common ways are the following:
 
 - Access context from `BlockInterface` instance. For example when need to get values from context during view building.
   Example:
   ```php
        /**
         * {@inheritdoc}
         */
        public function buildView(BlockView $view, BlockInterface $block, array $options)
        {
            $value = $block->getContext()->get('value-key');
        }
   ```
   
 - Access context using [ConfigExpression component](../../../../Component/ConfigExpression/README.md) by providing 
   expression as an option for some block.
   Example:
   ```yml
        actions:
            ...
            - @add:
                id: blockId
                parent: parentId
                blockType: typeName
                options:
                    optionName: { @value: $context.valueKey }
   ```
   

Context configurators
---------------------

It might be required to configure the context based on current application state, client setting or just define 
default values and so on. In order to prevent copy paste of boilerplate code **context configurators** have been introduced.
Each configurator should implement `\Oro\Component\Layout\ContextConfiguratorInterface` and be registered as a service 
in DI container with the tag `layout.context_configurator`. 

For debugging purposes `oro:layout:debug --context` command has been added, it allows to see how the context data-resolver will
be configured by **context configurators**.
