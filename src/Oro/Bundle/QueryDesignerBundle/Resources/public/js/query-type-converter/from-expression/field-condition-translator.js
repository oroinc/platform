import AbstractConditionTranslatorFromExpression from './abstract-condition-translator';
import {resolveFieldAST, normalizeBoundaryConditionAST, normalizeFieldConditionAST} from './tools';

class FieldConditionTranslatorFromExpression extends AbstractConditionTranslatorFromExpression {
    /**
     * @inheritDoc
     */
    translate(node) {
        node = normalizeFieldConditionAST(node);
        node = normalizeBoundaryConditionAST(node);
        const getAttrNode = resolveFieldAST(node);
        if (getAttrNode) {
            const filterConfig = this.getFilterConfig(getAttrNode);
            const filterTranslator = this.resolveFilterTranslator(filterConfig);
            if (filterTranslator) {
                return filterTranslator.tryToTranslate(node);
            }
        }
        return null;
    }

    /**
     * Fetches filter config from ExpressionLanguage AST node
     *
     * @param {Node} getAttrNode ExpressionLanguage AST node
     * @returns {Object}
     * @protected
     */
    getFilterConfig(getAttrNode) {
        const fieldId = this.fieldIdTranslator.translate(getAttrNode);
        const fieldSignature = this.fieldIdTranslator.provider.getFieldSignatureSafely(fieldId);
        return this.filterConfigProvider.getApplicableFilterConfig(fieldSignature);
    }
}

export default FieldConditionTranslatorFromExpression;
