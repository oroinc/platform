const updateState = (cm, phrase) => {
    const anchor = cm.state.selection.main.anchor;
    const startText = cm.state.sliceDoc(0, anchor).trimEnd();
    const endText = cm.state.sliceDoc(anchor).trimStart();

    return {
        changes: {
            from: 0,
            to: cm.state.doc.length,
            insert: `${startText}${phrase}${endText}`
        },
        selection: {
            anchor: `${startText}${phrase}`.length,
            head: `${startText}${phrase}`.length
        }
    };
};

export default [{
    name: 'equals',
    label: '=',
    order: 10,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' = '));
    }
}, {
    name: 'more_than',
    label: '>',
    order: 20,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' > '));
    }
}, {
    name: 'equals_greater_than',
    label: '≥',
    order: 30,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' >= '));
    }
}, {
    name: 'no_equals',
    label: '≠',
    order: 40,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' != '));
    }
}, {
    name: 'less_than',
    label: '<',
    order: 50,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' < '));
    }
}, {
    name: 'less_greater_than',
    label: '≤',
    order: 60,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' <= '));
    }
}, {
    name: 'addition',
    label: '+',
    order: 70,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' + '));
    }
}, {
    name: 'multiplication',
    label: '×',
    order: 80,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' * '));
    }
}, {
    name: 'precedence',
    label: '%',
    order: 90,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' % '));
    }
}, {
    name: 'subtraction',
    label: '−',
    order: 100,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' - '));
    }
}, {
    name: 'division',
    label: '÷',
    order: 110,
    enabled: true,
    handler(cm, e) {
        cm.focus();
        cm.dispatch(updateState(cm, ' / '));
    }
}, {
    name: 'rarentheses',
    label: '( )',
    order: 120,
    enabled: true,
    handler(cm) {
        const state = updateState(cm, ' () ');

        cm.focus();

        state.selection.anchor = state.selection.anchor - 2;
        state.selection.head = state.selection.head - 2;
        cm.dispatch(state);
    }
}, {
    name: 'in',
    label: 'In',
    order: 130,
    enabled: true,
    handler(cm, e) {
        cm.focus();

        const state = updateState(cm, ' in [] ');

        state.selection.anchor = state.selection.anchor - 2;
        state.selection.head = state.selection.head - 2;
        cm.dispatch(state);
    }
}, {
    name: 'and',
    label: 'And',
    order: 140,
    enabled: true,
    handler(cm, e) {
        cm.focus();
        cm.dispatch(updateState(cm, ' and '));
    }
}, {
    name: 'match',
    label: 'Match',
    order: 150,
    enabled: true,
    handler(cm, e) {
        cm.focus();

        const state = updateState(cm, ' matches containsRegExp() ');

        state.selection.anchor = state.selection.anchor - 2;
        state.selection.head = state.selection.head - 2;
        cm.dispatch(state);
    }
}, {
    name: 'not_in',
    label: 'Not In',
    order: 160,
    enabled: true,
    handler(cm) {
        cm.focus();

        const state = updateState(cm, ' not in [] ');

        state.selection.anchor = state.selection.anchor - 2;
        state.selection.head = state.selection.head - 2;
        cm.dispatch(state);
    }
}, {
    name: 'or',
    label: 'Or',
    order: 170,
    enabled: true,
    handler(cm) {
        cm.focus();
        cm.dispatch(updateState(cm, ' or '));
    }
}];
