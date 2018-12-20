# Expressions

The **OroLayoutBundle** brings additional functionality to the **LayoutComponent** via extension mechanism.
The idea is to avoid the dependency of the layout building process on raw data.
 
Consider the example when the visibility of a block depends on data availability. When layouts are built and rendered on the server side, data could be accessed directly using the DB layer, or passed through the [context](./layout_context.md). This, however, will not work for the layout that is rendered for a page in a single page application, where the data should be fetched using `AJAX` . So, in order to unify data access mechanism for different layout renderer **[Symfony expression](http://symfony.com/doc/current/components/expression_language/index.html)** was added.

The extension automatically processes **expressions** found in the **block view** variables during the `finishView` method 
execution. 

Expressions can be processed in two ways: if the context option `expressions_evaluate` is set to `true`, the expressions will be evaluated on the server side. Otherwise, they will be encoded into the context option format specified in `expressions_encoding` . 

**NOTE:** Expressions are evaluated only when the `finishView` event is triggered for the base block type. Evaluation of the complex
option values in the `buildBlock` and `buildView` methods is not supported. To deny expression usage, define the allowed option types.
Expressions are always passed in the [Options](../../../../Component/Layout/Block/Type/Options.php) object that wraps an `array` of expressions.

## Available Variables

You can access following variables in your expressions:

| Variable name | Description |
|------- |-------------|
| `context` | Refers to current [layout context](./layout_context.md) |
| `data` | Refers to [data accessor](./layout_data.md) |

**NOTE:** expression variables must begin with the equals sign, for example `'=data["backToUrl"]'`

## Encoders

Out of the box **OroPlatform** comes with `json` expression encoder, but encoders for other formats could be easily 
added to the system using DI tagging mechanism. An encoder class should implement the
[ExpressionEncoderInterface](../../Layout/Encoder/ExpressionEncoderInterface.php) and be registered as a service with the
 `layout.expression.encoder`tag. The `format` tag attribute is also required.

## Examples

The following is an example of using an expression in the layout update:

```yaml
layout:
    actions:
        - '@add':
            id: debug_init_script:
            parentId: head
            blockType: script
            options:
                visible: '=context["debug"]'
```

Here, we the *visible* option depends on the context value. The following is an example of using it in the view layer:
 
```php
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        // this operation better to use on finishView but if you are really sure you can write like this
        $view->vars['visible'] =  $options['visible'] ?? false;
    }
    
    public function finishView(BlockView $view, BlockInterface $block)
    {
       // we will depends on `expressions_evaluate` option we will have
       var_dump($view->vars['visible']);
       // a scalar value
       // bool(true)
       // or an encoded expression string
       // '=context["debug"]'
    }
```
