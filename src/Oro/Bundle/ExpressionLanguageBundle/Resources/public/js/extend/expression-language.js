define(function(require) {
    'use strict';

    var OriginalExpressionLanguage = require('oroexpressionlanguage/js/library/expression-language');
    var Lexer = require('oroexpressionlanguage/js/extend/lexer');
    var Parser = require('oroexpressionlanguage/js/extend/parser');

    /**
     * @inheritDoc
     */
    function ExpressionLanguage(cache, providers) {
        ExpressionLanguage.__super__.constructor.call(this, cache, providers);
    }

    ExpressionLanguage.prototype = Object.create(OriginalExpressionLanguage.prototype);
    ExpressionLanguage.__super__ = OriginalExpressionLanguage.prototype;

    Object.assign(ExpressionLanguage.prototype, {
        constructor: ExpressionLanguage,

        getLexer: function() {
            if (null === this.lexer) {
                this.lexer = new Lexer();
            }

            return this.lexer;
        },

        getParser: function() {
            if (null === this.parser) {
                this.parser = new Parser(this.functions);
            }

            return this.parser;
        }
    });
});
