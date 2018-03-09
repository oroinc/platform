define(function(require) {
    'use strict';

    return {
        // node
        ArgumentsNode: require('oroexpressionlanguage/js/library/node/arguments-node'),
        ArrayNode: require('oroexpressionlanguage/js/library/node/array-node'),
        BinaryNode: require('oroexpressionlanguage/js/extend/node/binary-node'),
        ConditionalNode: require('oroexpressionlanguage/js/library/node/conditional-node'),
        ConstantNode: require('oroexpressionlanguage/js/library/node/constant-node'),
        FunctionNode: require('oroexpressionlanguage/js/library/node/function-node'),
        GetAttrNode: require('oroexpressionlanguage/js/library/node/get-attr-node'),
        NameNode: require('oroexpressionlanguage/js/library/node/name-node'),
        UnaryNode: require('oroexpressionlanguage/js/library/node/unary-node'),

        // parser-cache
        ArrayParserCache: require('oroexpressionlanguage/js/library/parser-cache/array-parser-cache'),
        ParserCacheInterface: require('oroexpressionlanguage/js/library/parser-cache/parser-cache-interface'),

        Compiler: require('oroexpressionlanguage/js/library/compiler'),
        Expression: require('oroexpressionlanguage/js/library/expression'),
        ExpressionFunction: require('oroexpressionlanguage/js/library/expression-function'),
        ExpressionFunctionProviderInterface:
            require('oroexpressionlanguage/js/library/expression-function-provider-interface'),
        ExpressionLanguage: require('oroexpressionlanguage/js/extend/expression-language'),
        ExpressionSyntaxError: require('oroexpressionlanguage/js/library/expression-syntax-error'),
        Lexer: require('oroexpressionlanguage/js/extend/lexer'),
        ParsedExpression: require('oroexpressionlanguage/js/library/parsed-expression'),
        Parser: require('oroexpressionlanguage/js/extend/parser'),
        Token: require('oroexpressionlanguage/js/extend/token'),
        TokenStream: require('oroexpressionlanguage/js/library/token-stream')
    };
});
