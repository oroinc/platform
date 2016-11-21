Layout cache
==============

Layout cache is based on layout context.
It use [Context::getHash](../../../../Component/Layout/ContextInterface.php#L94) method to generate cache key.

Layout cache use `OroCacheBundle` for more information see [BlockViewCache](../../../../Component/Layout/BlockViewCache.php).

Last Modification Date
----------------------------
The layout context contains last modification date of files with layout updates. 
It registered with `layout.context_configurator` - [LastModifiedDateContextConfigurator](../../Layout/Extension/LastModifiedDateContextConfigurator.php)

BlockView Tree
----------------------------
Layout cache contains `root` [BlockView](../../../../Component/Layout/BlockView.php) with children and variables.
[BlockView](../../../../Component/Layout/BlockView.php) tree is serialized with `oro_layout.block_view_serializer`.
List of normalizers that used in `oro_layout.block_view_serializer`:
* `oro_layout.block_view_serializer.block_view_normalizer` - [BlockViewNormalizer](../../Layout/Serializer/BlockViewNormalizer.php)
* `oro_layout.block_view_serializer.expression_normalizer` - [ExpressionNormalizer](../../Layout/Serializer/ExpressionNormalizer.php)
* `oro_layout.option_value_bag_normaizer` - [OptionValueBagNormalizer](../../Layout/Serializer/OptionValueBagNormalizer.php)

All normalizers registered as a service in DI container with the tag `layout.block_view_serializer.normalizer`.

Expressions / evaluate and evaluate deferred
----------------------------
There are two groups of expressions in [BlockView](../../../../Component/Layout/BlockView.php) options:
* Context key `expressions_evaluate` - expressions that don't work with `data`. 
It evaluates before [BlockTypeInterface::buildBlock](../../../../Component/Layout/BlockTypeInterface.php#L19)
* Context key `expressions_evaluate_deferred` - expressions that work with `data`.
It evaluates after [BlockTypeInterface::finishView](../../../../Component/Layout/BlockTypeInterface.php#L51)

For example:

```
actions:
    ...
    - @add:
        id: blockId
        parent: parentId
        blockType: typeName
        options:
            optionName: '=context["valueKey"]'
```
It will be evaluated before [BlockTypeInterface::buildBlock](../../../../Component/Layout/BlockTypeInterface.php#L19) and result will be cached.


```
actions:
    ...
    - @add:
        id: blockId
        parent: parentId
        blockType: typeName
        options:
            optionName: '=data["valueKey"]'
```
It will be evaluated after [BlockTypeInterface::finishView](../../../../Component/Layout/BlockTypeInterface.php#L51) and result will not be cached.
