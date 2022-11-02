import expressionFunctionProviderInterface
    from 'oroexpressionlanguage/js/library/expression-function-provider-interface';
import ExpressionFunction from 'oroexpressionlanguage/js/library/expression-function';

/**
 * @implements ExpressionFunctionProviderInterface
 */
class TestProvider {
    getFunctions() {
        return [
            new ExpressionFunction('identity', function(input) {
                return input;
            }, function(values, input) {
                return input;
            })
        ];
    }
}

expressionFunctionProviderInterface.expectToBeImplementedBy(TestProvider.prototype);

export default TestProvider;
