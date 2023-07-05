import {basicLightTheme} from 'oroform/js/app/views/expression-editor-extensions/theme';
import {EditorView, keymap, lineNumbers} from '@codemirror/view';
import {autocompletion} from '@codemirror/autocomplete';
import {indentWithTab} from '@codemirror/commands';

export const editorExtensions = ({util}) => {
    return [
        autocompletion(),
        basicLightTheme,
        keymap.of([indentWithTab]),
        lineNumbers(),
        EditorView.lineWrapping
    ];
};

export default editorExtensions;
