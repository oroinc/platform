import __ from 'orotranslation/js/translator';
import {createErrorValidationConfig} from '../../utils/diagnostic';

export default function nonClosedBraces({node: nodeRef}) {
    if (nodeRef.name === 'BracesGroup') {
        if (nodeRef.node.lastChild.name !== 'BraceClose') {
            return createErrorValidationConfig(
                nodeRef.node.firstChild,
                __('oro.form.expression_editor.validation.braces.non_closed')
            );
        }
    }

    if (nodeRef.name === 'BraceClose' && !['BracesGroup', 'Function'].includes(nodeRef.node.parent.name)) {
        return createErrorValidationConfig(
            nodeRef,
            __('oro.form.expression_editor.validation.braces.non_opened')
        );
    }

    return null;
}
