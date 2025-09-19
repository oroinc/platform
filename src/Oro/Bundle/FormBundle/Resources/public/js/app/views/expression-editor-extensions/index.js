import {showPanel} from '@oroinc/codemirror-expression-editor';
import sidePanel from 'oroform/js/app/views/expression-editor-extensions/side-panel';
import expressionLinter from 'oroform/js/app/views/expression-editor-extensions/linter';

/**
 * Combine extensions for expression editor
 *
 * @param {object} params
 * @param {array} params.operationButtons
 * @param {number} params.interactionDelay
 * @param {number} params.linterDelay
 * @param {number} params.maxRenderedOptions
 * @returns [...extension]
 */
export const editorExtensions = ({
    util,
    operationButtons,
    linterDelay = 750
}) => {
    return [
        showPanel.of(sidePanel.bind(showPanel, operationButtons, util)),
        expressionLinter({util, linterDelay})
    ];
};

export default editorExtensions;
