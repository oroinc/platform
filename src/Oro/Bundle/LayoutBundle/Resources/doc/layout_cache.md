# Layout Cache

Layout cache is based on layout context.

It uses the [Context::getHash](../../../../Component/Layout/ContextInterface.php#L94) method to generate the cache key.

Layout cache uses `OroCacheBundle`. For more information, see the [BlockViewCache](../../../../Component/Layout/BlockViewCache.php).

## Last Modification Date

The layout context contains the last modification date of the files with layout updates. It is registered with the `layout.context_configurator` - [LastModifiedDateContextConfigurator](../../Layout/Extension/LastModifiedDateContextConfigurator.php)

## BlockView Tree

The layout cache contains the `root` [BlockView](../../../../Component/Layout/BlockView.php) with children and variables.

The [BlockView](../../../../Component/Layout/BlockView.php) tree is serialized with the `oro_layout.block_view_serializer`.

The following is the list of normalizers used in the `oro_layout.block_view_serializer`:

* `oro_layout.block_view_serializer.block_view_normalizer` - [BlockViewNormalizer](../../Layout/Serializer/BlockViewNormalizer.php)
* `oro_layout.block_view_serializer.expression_normalizer` - [ExpressionNormalizer](../../Layout/Serializer/ExpressionNormalizer.php)
* `oro_layout.option_value_bag_normaizer` - [OptionValueBagNormalizer](../../Layout/Serializer/OptionValueBagNormalizer.php)

All normalizers are registered as a service in the DI container with the  `layout.block_view_serializer.normalizer` tag.

## Expressions / evaluate and evaluate deferred

There are two groups of expressions in the [BlockView](../../../../Component/Layout/BlockView.php) options:

* Context key `expressions_evaluate` - expressions that do not work with `data`. 
It evaluates before [BlockTypeInterface::buildBlock](../../../../Component/Layout/BlockTypeInterface.php#L19)
* Context key `expressions_evaluate_deferred` - expressions that work with `data`.
It evaluates after [BlockTypeInterface::finishView](../../../../Component/Layout/BlockTypeInterface.php#L51)

For example:

```
actions:
    ...
    - '@add':
        id: blockId
        parentId: parentId
        blockType: typeName
        options:
            optionName: '=context["valueKey"]'
```
It will be evaluated before the [BlockTypeInterface::buildBlock](../../../../Component/Layout/BlockTypeInterface.php#L19) and the result will be cached.


```
actions:
    ...
    - '@add':
        id: blockId
        parentId: parentId
        blockType: typeName
        options:
            optionName: '=data["valueKey"]'
```
It will be evaluated after [BlockTypeInterface::finishView](../../../../Component/Layout/BlockTypeInterface.php#L51) and the result will not be cached.
