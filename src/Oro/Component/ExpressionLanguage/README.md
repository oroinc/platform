Oro ExpressionLanguage Component
============================

Oro ExpressionLanguage extended from Symfony ExpressionLanguage Component.

Main abilities are:
 * possibility to apply expression for each element of collections
 * access to object properties processed via \Oro\Component\PropertyAccess\PropertyAccessor
 * only `all` and `any` method allowed, and only for collections

## Example
 
`items.any(item.foo in ["bar"] and item.values.all(value.index > 10))`

In this example arguments of `any` and `all` method evaluates for each elements of `items` and `item.values` respectively.
For example, if in `items` we have 2 elements all expression will be executed as

`(item[0].foo in ["bar"] and item[0].values.all(value.index > 10)) OR (item[1].foo in ["bar"] and item[1].values.all(value.index > 10))`.

This example is for `any`, with `all` we will have `AND` instead of `OR`. 

