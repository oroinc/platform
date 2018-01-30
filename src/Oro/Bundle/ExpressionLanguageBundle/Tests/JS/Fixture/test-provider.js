define(function(require) {
    'use strict';

    var ExpressionFunctionProviderInterface =
        require('oroexpressionlanguage/js/library/expression-function-provider-interface');
    var ExpressionFunction = require('oroexpressionlanguage/js/library/expression-function');

    function TestProvider() {
        // nothing to do
    }

    TestProvider.prototype = {
        getFunctions: function() {
            return [
                new ExpressionFunction('identity', function(input) {
                    return input;
                }, function(values, input) {
                    return input;
                })
            ];
        }
    };

    ExpressionFunctionProviderInterface.expectToBeImplementedBy(TestProvider.prototype);

    return TestProvider;
});
