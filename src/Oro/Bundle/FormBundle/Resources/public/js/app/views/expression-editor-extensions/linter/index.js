import {linter, syntaxTree} from '@oroinc/codemirror-expression-editor';
import * as rules from './rules';
import linterPanel from './linter-panel';

export function expressionLinterWalker({util}) {
    return view => {
        const {state} = view;
        const found = [];

        syntaxTree(state).cursor().iterate(node => {
            Object.values(rules).forEach(rule => {
                const result = rule({
                    view,
                    node,
                    util
                });

                if (result) {
                    Array.isArray(result) ? found.push(...result) : found.push(result);
                }
            });
        });

        return found;
    };
}

export default function expressionLinter({util, linterDelay = 0} = {}) {
    return [
        linter(expressionLinterWalker({util}), {
            delay: linterDelay
        }),
        linterPanel()
    ];
}
