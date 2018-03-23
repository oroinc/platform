define(function(require) {
    'use strict';

    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;

    /**
     * Translates fieldId string (see definition in EntityStructureDataProvider) to AST of Expression Language
     *
     * @param {EntityStructureDataProvider} entityStructureDataProvider
     * @constructor
     * @throws TypeError if instance of EntityStructureDataProvider is missing
     */
    var FieldIdTranslator = function FieldIdTranslatorToExpression(entityStructureDataProvider) {
        if (!entityStructureDataProvider) {
            throw new TypeError(
                'Instance of `EntityStructureDataProvider` is required for `FieldIdTranslatorToExpression`');
        }
        this.provider = entityStructureDataProvider;
    };

    Object.assign(FieldIdTranslator.prototype, {
        constructor: FieldIdTranslator,

        /**
         * Translates fieldId string (see definition in EntityStructureDataProvider) to AST of Expression Language
         *
         * @param {fieldId} fieldId (see type definition in EntityStructureDataProvider)
         * @return {GetAttrNode}
         * @throws TypeError if fieldId is empty
         * @throws Error if rootEntity is not defined or it does not have alias
         */
        translate: function(fieldId) {
            if (!fieldId) {
                throw new TypeError('Empty `fieldId` can not be translated');
            }

            var name = this.provider.rootEntity && this.provider.rootEntity.get('alias');

            if (!name) {
                throw new Error('Alias of root entity is required for `fieldId` translation');
            }

            var properties = this.provider.getRelativePropertyPathByPath(fieldId).split('.');
            var node = new NameNode(name);

            for (var i = 0; i < properties.length; i++ ) {
                node = new GetAttrNode(
                    node, new ConstantNode(properties[i]), new ArgumentsNode(), GetAttrNode.PROPERTY_CALL
                );
            }

            return node;
        }
    });

    return FieldIdTranslator;
});
