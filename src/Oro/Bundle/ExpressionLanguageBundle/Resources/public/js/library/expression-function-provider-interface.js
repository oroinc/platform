define(function() {
    'use strict';

    var Interface = require('oroexpressionlanguage/js/library/interface');

    var ExpressionFunctionProviderInterface = new Interface({
        getFunctions: function() {}
    });

    return ExpressionFunctionProviderInterface;
});
