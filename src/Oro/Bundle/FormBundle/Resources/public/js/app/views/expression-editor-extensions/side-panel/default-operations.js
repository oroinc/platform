import {getResolvedSnippetByName} from '../utils/operators';

export const isNeedSpace = cm => {
    const {state} = cm;
    const {from, to} = state.selection.ranges[0];
    const char = state.sliceDoc(from - 1, to);
    return ![1, 2].includes(state.charCategorizer(from)(char)) || char === ')';
};

export const updateState = (cm, phrase) => {
    const {dispatch, state} = cm;
    const {to, from} = state.selection.ranges[0];

    return getResolvedSnippetByName(phrase, isNeedSpace(cm))({dispatch, state}, null, from, to);
};

export default [{
    name: 'equals',
    label: '=',
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
    handler(cm) {
        cm.focus();
        updateState(cm, '>');
    }
}, {
    name: 'equals_greater_than',
    label: '≥',
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
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '!=');
    }
}, {
    name: 'less_than',
    label: '<',
    order: 50,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '<');
    }
}, {
    name: 'less_greater_than',
    label: '≤',
    order: 60,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '<=');
    }
}, {
    name: 'addition',
    label: '+',
    order: 70,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '+');
    }
}, {
    name: 'multiplication',
    label: '×',
    order: 80,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '*');
    }
}, {
    name: 'precedence',
    label: '%',
    order: 90,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '%');
    }
}, {
    name: 'subtraction',
    label: '−',
    order: 100,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '-');
    }
}, {
    name: 'division',
    label: '÷',
    order: 110,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '/');
    }
}, {
    name: 'rarentheses',
    label: '( )',
    order: 120,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, '()');
    }
}, {
    name: 'in',
    label: 'In',
    order: 130,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'in');
    }
}, {
    name: 'and',
    label: 'And',
    order: 140,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'and');
    }
}, {
    name: 'match',
    label: 'Match',
    order: 150,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'matches');
    }
}, {
    name: 'not_in',
    label: 'Not In',
    order: 160,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'not in');
    }
}, {
    name: 'or',
    label: 'Or',
    order: 170,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, 'or');
    }
}];
