# Oro ExpressionLanguage Component

Oro ExpressionLanguage extends [Symfony ExpressionLanguage Component](https://symfony.com/doc/current/components/expression_language/introduction.html) to introduce expressions that are easier to use and that can additionally handle the collections of items.

## Why do I need it?
### * It's user-friendly and business oriented
With Oro ExpressionLanguage, you can include evaluating expressions into your application and be sure that your business users need minimum learning and no IT expertise or special skills to master the language. For example, when you need a flexibile solution for managing visibility of some entity, user can modify the visibility conditions in a web UI. They won't need any development background, as Oro ExplerssionLanguage is user friendly and business oriented. 

### * It supports collection validation with OR/AND logics 
When you have to validate all items in the collection (say, products in the order being submitted), or ensure that at least one value has a particular quality (for example meets bulk quantity requirements), you can easily check these facts using items.all(sub-condition) and items.any(sub-condition) phrases, where sub-condition is an expression that applies to every item. With the Symfony ExpressionLanguage, we would need to implement special functions or methods of collections and it would not necessarily be flexible and reusable. The expression would move from the UI to the code, which will cause difficulties in understanding for non-technical users. Finally, implementing a compex multi-level nested expression in the code might be really time-consuming challenge. All these considerations urged us to develop this improved component.

## How do I use it?
Sometimes you have to write an expression that takes into account the entire collection of products, like the one in the customer order during the checkout. For example, you need to ensure that all prodcuts are available (*enabled* property is set to *true*) and that product can be measured in items (product supports *item* as a *unit*). You can refer to the product fields to build the expression. We'll need at least the *product.units* collection that is an array of strings, and the *product.enabled* property that is boolean. Let's take a closer look at the resulting expression: 

`products.all(product.units.any(unit = 'item') and product.enabled)`

`products.all(` is a loop through the elements of `products` collection. It exposes every element of the collection inside the loop (in round brackets) as a `product`. 
in the example, for every product, you check that the folowing condition is true:
    
    `product.units.any(unit = 'item') and product.enabled`

Units is another collection being decomposing in the nested loop: `product.units.any(..)`

Inside the loop, we check every unit until we find the *item* as a *unit*.

Finally, we valuate a boolean parameter: `product.enabled`; it should be *true* as required by the logical *and* operation.

## What is the difference between Oro ExpressionLanguage and Symfony ExpressionLanguage?

1. In Oro ExpressionLanguage, `==` operator was removed and the `===` and `!==` operators were replaced with '=' (which stands for *identical*) and '!=' (which stands for *not identical*) respectively.
2. Oro ExpressionLanguage component uses *\Oro\Component\PropertyAccess\PropertyAccessor* to access the object properties.
3. `all` and `any` methods were added for arrays and *\Traversable* objects. A nested expression can act as an arguments for these methods, like in `product.units.any(unit = 'item')`. Note there are no quotes around the expression. 
3. `sum` method was added for arrays and *\Traversable* objects. Example, `lineItems.sum(lineItem.price)`. 
4. Oro ExpressionLanguage does not allow to call custom methods (other than `any` and `all`).

Other features of Symfony ExpressionLanguage have been gracefully inherited.

## Working with arrays and collections

As we've mentioned, `all`, `any`, and `sum` methods are available for arrays and *\Traversable* objects. These methods expect another expression as argument, for example, `products.all(product.enabled)`.

When you are using one of those methods, Oro ExpressionLanguage Component automatically adds special variable into the values of argument expression. If "products" array is a value of general expression, in method argument we will automatically get a "product" item, that is produced by stripping 's' from the array value to get a singular form. For collections of uncountable items, like `milk`, Oro ExpressionLanguage component adds 'Item' suffix, like in: `milk.all(milkItem.isfresh)`.

Now, let's see what actually happens when you call `all` and `any` methods. These methods generally follow the `and` and `or` logics when evaluating the nested expression for every element of array.

`items.all(nested_expression)` is `true` when the nested condition is satisfied for every item in the collection. When an item evaluation results in `false`, the `items.all()` immediately returns `false` wihtout processing the remaining items. 

Vise versa, `items.any(nested_expression)` is `true` if a nested expression evaluates to `true` for at least one item. Remaining items are not processed too.

When you call `items.sum(nested_expression)` method, all processing items will be summed one by one. Note that `nested_expression` should return numeric value only (for example, `items.sum(item.price) < 100`). If the expression evaluation results in an invalid (non-numeric) value, the method throws an exception.

## Example

To illustrate the Oro ExpressionLanguage in a PHP terms, here is an example of the expanded expression. Instead of using `all` and `any` methods, we'll explicitely iterate through the collection elements.

For the input context data:
```yaml
products:
    -
        enabled: true
        unit: 
            - 'set'
            - 'item'
    -
        enabled: true
        unit: 
            - 'item'
```
the Oro ExpressionLanguage phrase:
`products.all(product.units.any(unit = 'item') and product.enabled)`

will expand or translate into:
```
((products[0].unit[0] === 'item' or products[0].unit[1] === 'item') and products[0].enabled)
and
((products[1].unit[0] === 'item') and products[1].enabled)
```
