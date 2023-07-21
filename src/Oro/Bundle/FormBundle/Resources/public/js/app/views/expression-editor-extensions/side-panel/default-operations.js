import {getOperatorSnippet} from '../utils/operators';

const updateState = (cm, phrase) => {
    const {dispatch, state} = cm;
    const {to, from} = state.selection.ranges[0];

    if (typeof phrase === 'function') {
        return phrase({dispatch, state}, null, from, to);
    }

    return dispatch({
        changes: {
            from,
            to,
            insert: phrase
        },
        selection: {
            anchor: to + phrase.length,
            head: to + phrase.length
        }
    });
};

export default [{
    name: 'equals',
    label: '=',
    order: 10,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('=='));
    }
}, {
    name: 'more_than',
    label: '>',
    order: 20,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('>'));
    }
}, {
    name: 'equals_greater_than',
    label: '≥',
    order: 30,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('>='));
    }
}, {
    name: 'no_equals',
    label: '≠',
    order: 40,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('!='));
    }
}, {
    name: 'less_than',
    label: '<',
    order: 50,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('<'));
    }
}, {
    name: 'less_greater_than',
    label: '≤',
    order: 60,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('<='));
    }
}, {
    name: 'addition',
    label: '+',
    order: 70,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('+'));
    }
}, {
    name: 'multiplication',
    label: '×',
    order: 80,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('*'));
    }
}, {
    name: 'precedence',
    label: '%',
    order: 90,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('%'));
    }
}, {
    name: 'subtraction',
    label: '−',
    order: 100,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('-'));
    }
}, {
    name: 'division',
    label: '÷',
    order: 110,
    enabled: true,
    handler(cm, e) {
        cm.focus();
        updateState(cm, getOperatorSnippet('/'));
    }
}, {
    name: 'rarentheses',
    label: '( )',
    order: 120,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('()'));
    }
}, {
    name: 'in',
    label: 'In',
    order: 130,
    enabled: true,
    handler(cm, e) {
        cm.focus();
        updateState(cm, getOperatorSnippet('in'));
    }
}, {
    name: 'and',
    label: 'And',
    order: 140,
    enabled: true,
    handler(cm, e) {
        cm.focus();
        updateState(cm, getOperatorSnippet('and'));
    }
}, {
    name: 'match',
    label: 'Match',
    order: 150,
    enabled: true,
    handler(cm, e) {
        cm.focus();
        updateState(cm, getOperatorSnippet('matches'));
    }
}, {
    name: 'not_in',
    label: 'Not In',
    order: 160,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('not in'));
    }
}, {
    name: 'or',
    label: 'Or',
    order: 170,
    enabled: true,
    handler(cm) {
        cm.focus();
        updateState(cm, getOperatorSnippet('or'));
    }
}];
