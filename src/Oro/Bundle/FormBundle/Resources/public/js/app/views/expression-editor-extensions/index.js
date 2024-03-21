import {Facet} from '@codemirror/state';
import {EditorView, keymap, showPanel, tooltips, showTooltip} from '@codemirror/view';
import {autocompletion, startCompletion, closeBrackets} from '@codemirror/autocomplete';
import {indentWithTab, defaultKeymap, history} from '@codemirror/commands';
import sidePanel from 'oroform/js/app/views/expression-editor-extensions/side-panel';
import {syntaxHighlighting, bracketMatching} from '@codemirror/language';
import expressionLinter from 'oroform/js/app/views/expression-editor-extensions/linter';
import elementTooltip from './element-tooltip';

import {symfonyExpressionLanguageHighlightStyle} from './language/highlight';
import {symfonyExpression} from './language';

const tooltipOptionsFacet = Facet.define();
const tooltipOptionsFacetHost = tooltipOptionsFacet.compute([showTooltip], state => {
    const tooltips = state.facet(showTooltip).filter(t => t) || [];

    tooltips.forEach(tooltip => tooltip.arrow = false);

    return tooltips;
});

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
    interactionDelay = 75,
    linterDelay = 750,
    maxRenderedOptions = 20,
    dataSource = {},
    getDataSourceCallback
}) => {
    return [
        symfonyExpression(util),
        keymap.of([indentWithTab, defaultKeymap, {
            key: 'Alt-ArrowDown',
            run: startCompletion
        }]),
        autocompletion({
            icons: false,
            interactionDelay,
            maxRenderedOptions,
            compareCompletions(a, b) {
                return a - b;
            }
        }),
        showPanel.of(sidePanel.bind(showPanel, operationButtons)),
        EditorView.lineWrapping,
        EditorView.editorAttributes.of({
            'class': 'expression-editor'
        }),
        syntaxHighlighting(symfonyExpressionLanguageHighlightStyle),
        EditorView.updateListener.of(event => {
            if ([2, 4, 6].includes(event.flags)) {
                setTimeout(() => startCompletion(event.view));
            }
        }),
        EditorView.domEventHandlers({
            focusin(event, view) {
                setTimeout(() => startCompletion(view));
            }
        }),
        tooltips({
            position: 'fixed',
            parent: document.body
        }),
        expressionLinter({util, linterDelay}),
        closeBrackets(),
        bracketMatching(),
        history(),
        tooltipOptionsFacetHost,
        elementTooltip({util, dataSource, getDataSourceCallback})
    ];
};

export default editorExtensions;
