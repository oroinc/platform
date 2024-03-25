import __ from 'orotranslation/js/translator';
import {createErrorValidationConfig} from '../../utils/diagnostic';

export default function nonClosedBracket({node: nodeRef}) {
    if (nodeRef.name === 'BracketGroup') {
        if (nodeRef.node.lastChild.name !== 'BracketClose') {
            return createErrorValidationConfig(
                nodeRef.node.firstChild,
                __('oro.form.expression_editor.validation.brackets.non_closed')
            );
        }
    }

    if (nodeRef.name === 'BracketClose' && !['BracketGroup', 'Function'].includes(nodeRef.node.parent.name)) {
        return createErrorValidationConfig(
            nodeRef,
            __('oro.form.expression_editor.validation.brackets.non_opened')
        );
    }

    return null;
}
