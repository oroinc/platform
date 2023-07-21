import {EditorView, keymap, showPanel} from '@codemirror/view';
import {autocompletion, startCompletion, closeBrackets} from '@codemirror/autocomplete';
import {indentWithTab, defaultKeymap} from '@codemirror/commands';
import sidePanel from 'oroform/js/app/views/expression-editor-extensions/side-panel';
import {syntaxHighlighting, bracketMatching} from '@codemirror/language';

import {symfonyExpressionLanguageHighlightStyle} from './language/highlight';
import {symfonyExpression} from './language';

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
        EditorView.updateListener.of(event => setValue(event.state.doc.toString())),
        EditorView.domEventHandlers({
            focusin(event, view) {
                setTimeout(() => startCompletion(view));
            }
        }),
        closeBrackets(),
        bracketMatching()
    ];
};

export default editorExtensions;
