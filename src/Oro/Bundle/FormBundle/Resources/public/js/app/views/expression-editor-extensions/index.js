import {Facet} from '@codemirror/state';
import {EditorView, keymap, showPanel, tooltips, showTooltip} from '@codemirror/view';
import {autocompletion, startCompletion, closeBrackets} from '@codemirror/autocomplete';
import {indentWithTab, defaultKeymap, history} from '@codemirror/commands';
import sidePanel from 'oroform/js/app/views/expression-editor-extensions/side-panel';
import {syntaxHighlighting, bracketMatching} from '@codemirror/language';
import expressionLinter from 'oroform/js/app/views/expression-editor-extensions/linter';

import {symfonyExpressionLanguageHighlightStyle} from './language/highlight';
import {symfonyExpression} from './language';

const tooltipOptionsFacet = Facet.define();
const tooltipOptionsFacetHost = tooltipOptionsFacet.compute([showTooltip], state => {
    const tooltips = state.facet(showTooltip).filter(t => t) || [];

    tooltips.forEach(tooltip => tooltip.arrow = true);

    return tooltips;
});

export const editorExtensions = ({util, operationButtons, setValue}) => {
    return [
        symfonyExpression(util),
        keymap.of([indentWithTab, defaultKeymap, {
            key: 'Alt-ArrowDown',
            run: startCompletion
        }]),
        autocompletion({
            icons: false
        }),
        showPanel.of(sidePanel.bind(showPanel, operationButtons)),
        EditorView.lineWrapping,
        EditorView.editorAttributes.of({
            'class': 'expression-editor'
        }),
        syntaxHighlighting(symfonyExpressionLanguageHighlightStyle),
        EditorView.updateListener.of(event => {
            if (event.flags === 4) {
                setTimeout(() => startCompletion(event.view));
            }

            setValue(event.state.doc.toString());
        }),
        EditorView.domEventHandlers({
            focusin(event, view) {
                setTimeout(() => startCompletion(view));
            }
        }),
        tooltips({
            position: 'absolute'
        }),
        expressionLinter({util}),
        closeBrackets(),
        bracketMatching(),
        history(),
        tooltipOptionsFacetHost
    ];
};

export default editorExtensions;
