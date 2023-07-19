# ExpressionLanguage JS extension

The extension of JS library implements Oro extensions of Symfony ExpressionLanguage ([documentation](../../../../../Component/ExpressionLanguage/README.md))
and also adds some convenient features.

## Token

Source: [`oroexpressionlanguage/js/extend/token.js`](../../public/js/extend/token.js)

- Added token `length` as a fourth parameter to the constructor to help determine what the current token is (using the token length and the cursor position).
- Added an `ERROR_TYPE` token type to help tokenize expressions that contain errors.

## Lexer

Source: [`oroexpressionlanguage/js/extend/lexer.js`](../../public/js/extend/lexer.js)

- Overridden the `OPERATION_REGEXP` property to match the backend extension.
- Added the `tokenizeForce` method to tokenize expressions that contain errors.

## Parser

Source: [`oroexpressionlanguage/js/extend/parser.js`](../../public/js/extend/parser.js)

- Overriden the `parseExpression` method to use extended `BinaryNode`.

## ExpressionLanguage

Source: [`oroexpressionlanguage/js/extend/expression-language.js`](../../public/js/extend/expression-language.js)

- Overridden the `getLexer` and `getParser` methods to use the extended lexer and parser.
