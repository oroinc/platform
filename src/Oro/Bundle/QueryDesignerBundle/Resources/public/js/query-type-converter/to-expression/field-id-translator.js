import {ArgumentsNode, ConstantNode, GetAttrNode, NameNode} from 'oroexpressionlanguage/js/expression-language-library';

/**
 * Translates fieldId string (see definition in EntityStructureDataProvider) to AST of Expression Language
 */
class FieldIdTranslatorToExpression {
    /**
     * @param {EntityStructureDataProvider} entityStructureDataProvider
     * @constructor
     * @throws TypeError if instance of EntityStructureDataProvider is missing
     */
    constructor(entityStructureDataProvider) {
        if (!entityStructureDataProvider) {
            throw new TypeError(
                'Instance of `EntityStructureDataProvider` is required for `FieldIdTranslatorToExpression`');
        }
        this.provider = entityStructureDataProvider;
    }

    /**
     * Translates fieldId string (see definition in EntityStructureDataProvider) to AST of Expression Language
     *
     * @param {fieldId} fieldId (see type definition in EntityStructureDataProvider)
     * @return {GetAttrNode}
     * @throws TypeError if fieldId is empty
     * @throws Error if rootEntity is not defined, or it does not have alias
     */
    translate(fieldId) {
        if (!fieldId) {
            throw new TypeError('Empty `fieldId` can not be translated');
        }

        const name = this.provider.rootEntity && this.provider.rootEntity.get('alias');

        if (!name) {
            throw new Error('Alias of root entity is required for `fieldId` translation');
        }

        const properties = this.provider.getRelativePropertyPathByPath(fieldId).split('.');
        let node = new NameNode(name);

        for (let i = 0; i < properties.length; i++ ) {
            node = new GetAttrNode(
                node, new ConstantNode(properties[i]), new ArgumentsNode(), GetAttrNode.PROPERTY_CALL
            );
        }

        return node;
    }
}

export default FieldIdTranslatorToExpression;
