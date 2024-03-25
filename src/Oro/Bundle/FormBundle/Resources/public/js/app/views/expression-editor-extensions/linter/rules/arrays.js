import __ from 'orotranslation/js/translator';
import {createErrorValidationConfig} from '../../utils/diagnostic';

export default function Arrays({node: nodeRef}) {
    if (nodeRef.name === 'Array') {
        const node = nodeRef.node;
        const results = [];

        if (node.firstChild.name !== 'SquareBracketOpen') {
            results.push(
                createErrorValidationConfig(nodeRef, __('oro.form.expression_editor.validation.array.open'), {
                    from: node.from - 1,
                    to: node.from
                })
            );
        }

        if (node.lastChild.name !== 'SquareBracketClose') {
            results.push(
                createErrorValidationConfig(nodeRef, __('oro.form.expression_editor.validation.array.close'), {
                    from: node.to - 1,
                    to: node.to
                })
            );
        }

        node.getChildren('ArrayItem').forEach((arrayItem, index, collection) => {
            if (arrayItem.nextSibling.name !== 'Comma' && index < collection.length - 1) {
                results.push(
                    createErrorValidationConfig(
                        arrayItem,
                        __('oro.form.expression_editor.validation.array.comma'),
                        {
                            from: arrayItem.to,
                            to: arrayItem.to + 1
                        }
                    )
                );
            }
        });

        return results;
    }

    return null;
}
