import Interface from 'oroexpressionlanguage/js/library/interface';

/**
 * @interface ExpressionFunctionProviderInterface
 */
class ExpressionFunctionProviderInterface {
    getFunctions() {}
}

const expressionFunctionProviderInterface = new Interface(ExpressionFunctionProviderInterface.prototype);

export default expressionFunctionProviderInterface;
