import __ from 'orotranslation/js/translator';
import {showPanel} from '@codemirror/view';
import {StateField} from '@codemirror/state';
import {diagnosticCount} from '@codemirror/lint';

function createLinterPanel(view) {
    const dom = document.createElement('div');
    dom.innerText = __('oro.form.expression_editor.validation.has_errors');

    return {
        dom,
        top: false,
        mount() {
            this.dom.parentNode.classList.add('cm-linter-bottom-panel');
            this.dom.parentNode.style.bottom = '';
            view.scrollDOM.classList.add('cm-has-linter-panel');
        },
        destroy() {
            view.scrollDOM.classList.remove('cm-has-linter-panel');
        }
    };
}

const linterPanelState = StateField.define({
    create: () => false,

    update(value, tr) {
        return diagnosticCount(tr.state) ? createLinterPanel(tr.view) : false;
    },

    provide: f => showPanel.from(f, on => on ? createLinterPanel : null)
});

export default function linterPanel() {
    return [linterPanelState];
}
