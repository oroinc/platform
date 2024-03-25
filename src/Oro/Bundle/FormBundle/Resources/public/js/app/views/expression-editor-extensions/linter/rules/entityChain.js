import __ from 'orotranslation/js/translator';
import {createErrorValidationConfig} from '../../utils/diagnostic';

const createEntityChainChecker = (util, view) => {
    const getNodeContent = node => view.state.doc.sliceString(node.from, node.to).trim();

    const entities = util.expressionOperandTypeValidator.entities;

    return chain => {
        const results = [];
        const [entityNode, ...propertyNodes] = chain;
        const entityName = getNodeContent(entityNode);
        const foundEntity = entities.find(entity => entity.name === entityName.replace(/\[([\S\s?]+)?\]/g, ''));

        if (!foundEntity) {
            results.push(
                createErrorValidationConfig(entityNode, __('oro.form.expression_editor.validation.entity_name', {
                    entityName
                }))
            );
        }

        if (entityNode && !propertyNodes.length) {
            results.push(
                createErrorValidationConfig(
                    entityNode,
                    __('oro.form.expression_editor.validation.property_path.like_variable', {
                        name: entityName
                    })
                )
            );
        }

        const {validations} = propertyNodes.reduce(({chainContent, validations}, node) => {
            const [isValid, exception] = util.validate(
                [...chainContent, getNodeContent(node)].join(util.strings.childSeparator)
                , true);

            if (!isValid) {
                validations.push(createErrorValidationConfig(node, exception.message));
            }

            chainContent.push(getNodeContent(node));

            return {chainContent, validations};
        }, {
            chainContent: [entityName],
            validations: []
        });

        return [...results, ...validations];
    };
};

export default function EntityChain({view, node: nodeRef, util}) {
    const chainValidate = createEntityChainChecker(util, view);

    if (nodeRef.name === 'Entity') {
        const entity = nodeRef.node;
        const entityName = entity.getChild('EntityName');
        const propertyNames = entity.getChildren('PropertyName');
        return chainValidate([entityName, ...propertyNames]);
    }

    return null;
}
