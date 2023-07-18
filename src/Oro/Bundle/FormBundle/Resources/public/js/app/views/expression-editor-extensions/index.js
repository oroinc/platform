import {basicLightTheme} from 'oroform/js/app/views/expression-editor-extensions/theme';
import {EditorView, keymap, showPanel} from '@codemirror/view';
import {autocompletion} from '@codemirror/autocomplete';
import {indentWithTab} from '@codemirror/commands';
import sidePanel from 'oroform/js/app/views/expression-editor-extensions/side-panel';

export const editorExtensions = ({util, operationButtons}) => {
    return [
        autocompletion(),
        basicLightTheme,
        keymap.of([indentWithTab]),
        EditorView.lineWrapping,
        showPanel.of(sidePanel.bind(showPanel, operationButtons))
    ];
};

export default editorExtensions;
