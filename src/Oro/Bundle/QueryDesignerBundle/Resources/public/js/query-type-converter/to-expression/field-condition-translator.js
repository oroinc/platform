import AbstractConditionTranslatorToExpression from './abstract-condition-translator';

class FieldConditionTranslatorToExpression extends AbstractConditionTranslatorToExpression {
    /**
     * @inheritDoc
     */
    getConditionSchema() {
        return {
            type: 'object',
            required: ['columnName', 'criterion'],
            properties: {
                columnName: {type: 'string'},
                criterion: {
                    type: 'object',
                    required: ['data', 'filter'],
                    properties: {
                        filter: {type: 'string'},
                        data: {type: 'object'}
                    }
                }
            }
        };
    }

    /**
     * @inheritDoc
     */
    test(condition) {
        let result = super.test(condition);

        if (result) {
            const filterTranslator = this.resolveFilterTranslator(condition.criterion.filter) || false;
            result = filterTranslator && filterTranslator.test(condition.criterion.data);
        }

        return result;
    }

    /**
     * @inheritDoc
     */
    translate(condition) {
        const filterTranslator = this.resolveFilterTranslator(condition.criterion.filter);
        const leftOperand = this.fieldIdTranslator.translate(condition.columnName);

        return filterTranslator.translate(leftOperand, condition.criterion.data);
    }
}

export default FieldConditionTranslatorToExpression;
