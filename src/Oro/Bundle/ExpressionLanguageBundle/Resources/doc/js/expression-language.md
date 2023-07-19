Expression Language
===================

The library is created by converting a Symfony's Expression Language component (see [documentation](https://symfony.com/doc/current/components/expression_language.html))
form PHP to JS. This implementation allows to validate, compile and evaluate an expression on client side in the same way like backend symfony component.

As result library can convert an expression to JS code. Or can evaluate expression to some value using transmitted JS object as context.

For consistency and easy upgrading process the files structure of library is preserved, as well as classes and tests of original PHP component.

Pack of polyfill methods that implement specific PHP functions are added to [`php-to-js`](../../public/lib/php-to-js) directory. 

Since different nature and environment PHP and JS there are several differences:

- the library doesn't support `constant("...")` function<sup>[[?](https://symfony.com/doc/2.8/components/expression_language/syntax.html#component-expression-functions)]</sup> in an expression (client code has no access to backend constants)
- arrays in expressions are represent as objects in compiled JS code (since arrays in PHP can contain non-numeric keys),
e.i. expression `["a", "b"]` is compiled to `{0: "a", 1: "b"}`
- skipped implementation of `SerializedParsedExpression`<sup>[[?](https://github.com/symfony/symfony/blob/2.8/src/Symfony/Component/ExpressionLanguage/SerializedParsedExpression.php)]</sup>, due to absence of native serialization in JS and lack of real use cases
