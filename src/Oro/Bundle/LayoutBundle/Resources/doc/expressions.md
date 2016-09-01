Expressions
===========

The **OroLayoutBundle** brings additional functionality to the **LayoutComponent** via extension mechanism.
The idea is to avoid dependency of the layout building process on raw data. Let's imagine that visibility of some 
block depends on data availability. In case when layout are built and rendered on server side data could be accessed 
directly using DB layer or passed through the [context](./layout_context.md), but for the layout that is rendered for
some page in a single page application, where the data should be fetched using `AJAX` it will not work. So, in order 
to unify data access mechanism for different layout renderer **[Symfony expression](http://symfony.com/doc/current/components/expression_language/index.html)** was added.

Automatically the extension processes **expressions** that are found in **block view** variables during `finishView` method 
execution. 
There are two modes how expressions could be processed: if context option `expressions_evaluate` set to `true` 
then expressions will be evaluated on server side, or will be encoded to specified in 
`expressions_encoding` context option format otherwise. 

**NOTE:** Expressions are evaluated only on `finishView` event triggered for the base block type. Evaluation of the complex
option values in `buildBlock` and `buildView` methods is not supported. To deny expression usage, define the allowed option types.
Expressions are always passed in the [Options](../../../../Component/Layout/Block/Type/Options.php) object that wraps an `array` of expressions.

Available variables
-------------------

You can access following variables in your expressions:

| Variable name | Description |
|------- |-------------|
| `context` | Refers to current [layout context](./layout_context.md) |
| `data` | Refers to [data accessor](./layout_data.md) |

**NOTE:** expression variables must begin from equals, for example `'=data["backToUrl"]'`

Encoders
--------

Out of the box the **Oro Platform** comes with `json` expression encoder, but encoders for other formats could be easily 
added to the system using DI tagging mechanism. An encoder class should implements
[ExpressionEncoderInterface](../../Layout/Encoder/ExpressionEncoderInterface.php) and be registered as a service with 
tag `oro_layout.expression.encoder` and tag attribute `format` is required.

Examples
--------

Usage of an expression in layout update:

```yaml
layout:
    actions:
        - @add:
            id: debug_init_script:
            parentId: head
            blockType: script
            options:
                visible: '=context["debug"]'
```

Here we can see that *visible* option depends on context value, let's review how it could be used in a view layer:

```php
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        // this operation better to use on finishView but if you are really sure you can write like this
        $view->vars['visible'] =  $options->get('visible', false);
    }
    
    public function finishView(BlockView $view, BlockInterface $block, Options $options)
    {
       // we will depends on `expressions_evaluate` option we will have
       var_dump($view->vars['visible']);
       // a scalar value
       // bool(true)
       // or an encoded expression string
       // '=context["debug"]'
    }
```
