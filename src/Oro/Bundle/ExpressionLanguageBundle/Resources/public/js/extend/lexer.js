define(function(require) {
    'use strict';

    var OriginalLexer = require('oroexpressionlanguage/js/library/lexer');

    function Lexer() {
        Lexer.__super__.constructor.call(this);
    }

    Lexer.prototype = Object.create(OriginalLexer.prototype);
    Lexer.__super__ = OriginalLexer.prototype;

    Object.defineProperties(Lexer.prototype, {
        // added '='
        // removed '===', '!=='
        OPERATION_REGEXP: {value: /^(not in(?=[\s(])|not(?=[\s(])|and(?=[\s(])|>=|or(?=[\s(])|<=|\*\*|\.\.|in(?=[\s(])|&&|\|\||matches|==|=|!=|\*|~|%|\/|>|\||!|\^|&|\+|<|-)/}
    });

    return Lexer;
});
