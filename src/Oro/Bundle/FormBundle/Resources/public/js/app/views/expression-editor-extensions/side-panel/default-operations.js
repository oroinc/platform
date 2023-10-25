import __ from 'orotranslation/js/translator';
import {getResolvedSnippetByName} from '../utils/operators';

export const isNeedSpace = cm => {
    const {state} = cm;
    const {from, to} = state.selection.ranges[0];
    const char = state.sliceDoc(from - 1, to);
    return ![1, 2].includes(state.charCategorizer(from)(char)) || char === ')';
};

export const updateState = (cm, phrase) => {
    const {dispatch, state} = cm;
    const {to, from, empty} = state.selection.ranges[0];

    return getResolvedSnippetByName(
        phrase,
        empty && isNeedSpace(cm)
    )({dispatch, state}, null, from, to);
};

export default [{
    name: 'equals',
    label: '=',
    title: __('oro.form.expression_editor.operators.detail.equal'),
    order: 10,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '==');
    }
}, {
    name: 'more_than',
    label: '>',
    order: 20,
    enabled: true,
    title: __('oro.form.expression_editor.operators.detail.greater_than'),
    handler(cm) {
        cm.focus();
        updateState(cm, '>');
    }
}, {
    name: 'equals_greater_than',
    label: '≥',
    title: __('oro.form.expression_editor.operators.detail.greater_than_or_equal'),
    order: 30,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '>=');
    }
}, {
    name: 'no_equals',
    label: '≠',
    order: 40,
    title: __('oro.form.expression_editor.operators.detail.not_equal'),
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '!=');
    }
}, {
    name: 'less_than',
    label: '<',
    title: __('oro.form.expression_editor.operators.detail.less_than'),
    order: 50,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '<');
    }
}, {
    name: 'less_greater_than',
    label: '≤',
    title: __('oro.form.expression_editor.operators.detail.less_than_or_equal'),
    order: 60,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '<=');
    }
}, {
    name: 'addition',
    label: '+',
    title: __('oro.form.expression_editor.operators.detail.addition'),
    order: 70,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '+');
    }
}, {
    name: 'multiplication',
    label: '×',
    title: __('oro.form.expression_editor.operators.detail.multiplication'),
    order: 80,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '*');
    }
}, {
    name: 'precedence',
    label: '%',
    title: __('oro.form.expression_editor.operators.detail.remainder'),
    order: 90,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '%');
    }
}, {
    name: 'subtraction',
    label: '−',
    title: __('oro.form.expression_editor.operators.detail.subtraction'),
    order: 100,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '-');
    }
}, {
    name: 'division',
    label: '÷',
    title: __('oro.form.expression_editor.operators.detail.division'),
    order: 110,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '/');
    }
}, {
    name: 'parentheses',
    label: '( )',
    title: __('oro.form.expression_editor.operators.detail.parentheses'),
    order: 120,
    enabled: true,
    handler(cm) {
        const {state} = cm;
        const selection = state.selection.ranges.at(0);
        const selectedContent = state.doc.sliceString(selection.from, selection.to);

        cm.focus();
        updateState(cm, selectedContent ? `(${selectedContent})` : '()');
    }
}, {
    name: 'in',
    label: __('oro.form.expression_editor.operators.detail.in'),
    title: __('oro.form.expression_editor.operators.detail.in'),
    order: 130,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'in');
    }
}, {
    name: 'not_in',
    label: __('oro.form.expression_editor.operators.detail.not_in'),
    title: __('oro.form.expression_editor.operators.detail.not_in'),
    order: 140,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'not in');
    }
}, {
    name: 'and',
    label: __('oro.form.expression_editor.operators.detail.and'),
    title: __('oro.form.expression_editor.operators.detail.and'),
    order: 150,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'and');
    }
}, {
    name: 'or',
    label: __('oro.form.expression_editor.operators.detail.or'),
    title: __('oro.form.expression_editor.operators.detail.or'),
    order: 160,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'or');
    }
}, {
    name: 'yes',
    label: __('oro.form.expression_editor.operators.detail.yes'),
    title: __('oro.form.expression_editor.operators.detail.yes'),
    order: 170,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'yes');
    }
}, {
    name: 'no',
    label: __('oro.form.expression_editor.operators.detail.no'),
    title: __('oro.form.expression_editor.operators.detail.no'),
    order: 180,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'no');
    }
}, {
    name: 'match',
    label: __('oro.form.expression_editor.operators.detail.match'),
    title: __('oro.form.expression_editor.operators.detail.match'),
    extraClassName: 'cm-btn-half',
    order: 190,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'matches');
    }
}, {
    name: 'does_not_match',
    label: __('oro.form.expression_editor.operators.detail.does_not_match'),
    title: __('oro.form.expression_editor.operators.detail.does_not_match'),
    extraClassName: 'cm-btn-half',
    order: 200,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'does not match');
    }
}, {
    name: 'is_empty',
    label: __('oro.form.expression_editor.operators.detail.is_empty'),
    title: __('oro.form.expression_editor.operators.detail.is_empty'),
    extraClassName: 'cm-btn-half',
    order: 210,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'is empty');
    }
}, {
    name: 'is_not_empty',
    label: __('oro.form.expression_editor.operators.detail.is_not_empty'),
    title: __('oro.form.expression_editor.operators.detail.is_not_empty'),
    extraClassName: 'cm-btn-half',
    order: 220,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'is not empty');
    }
}, {
    name: 'between',
    label: __('oro.form.expression_editor.operators.detail.between'),
    title: __('oro.form.expression_editor.operators.detail.between'),
    extraClassName: 'cm-btn-full',
    order: 230,
    enabled: false,
    handler(cm) {
        cm.focus();
        updateState(cm, 'between');
    }
}, {
    name: 'not_between',
    label: __('oro.form.expression_editor.operators.detail.not_between'),
    title: __('oro.form.expression_editor.operators.detail.not_between'),
    extraClassName: 'cm-btn-full',
    order: 240,
    enabled: false,
    handler(cm) {
        cm.focus();
        updateState(cm, 'not between');
    }
}];
