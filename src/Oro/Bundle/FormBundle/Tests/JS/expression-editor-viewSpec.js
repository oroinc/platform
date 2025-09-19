import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import ExpressionEditorView from 'oroform/js/app/views/expression-editor-view';
import ExpressionEditorUtil from 'oroform/js/expression-editor-util';
import DataProviderMock from './Fixture/entity-structure-data-provider-mock.js';

// fixtures
import entitiesData from './Fixture/entities-data.json';
import html from 'text-loader!./Fixture/expression-editor-template.html';
import dataSource from 'text-loader!./Fixture/data-source.html';
import 'jasmine-jquery';

import {
    startCompletion,
    completionStatus,
    acceptCompletion,
    snippet,
    moveCompletionSelection,
    currentCompletions,
    forEachDiagnostic,
    syntaxTree
} from '@oroinc/codemirror-expression-editor';
import defaultOperations from 'oroform/js/app/views/expression-editor-extensions/side-panel/default-operations';

// variables
let expressionEditorView = null;
let cm = null;
const moveCompletionForward = moveCompletionSelection(true);

function createEditorOptions(customOptions) {
    const utilOptions = _.result(customOptions, 'util');
    let viewOptions = _.omit(customOptions, 'util');
    viewOptions = Object.assign({
        autoRender: true,
        el: '#expression-editor',
        dataSource: {
            pricelist: dataSource
        },
        linterDelay: 0,
        interactionDelay: 0
    }, viewOptions);
    if (utilOptions !== null) {
        viewOptions.util = new ExpressionEditorUtil(Object.assign({
            entityDataProvider: new DataProviderMock(entitiesData),
            dataSourceNames: ['pricelist'],
            supportedNames: ['pricelist', 'product'],
            itemLevelLimit: 3
        }, utilOptions || {}));
    }
    return viewOptions;
}

const flushContent = cm => {
    cm.dispatch({
        changes: {
            from: 0,
            to: cm.state.doc.toString().length,
            insert: ''
        }
    });
};

const updateState = (cm, phrase) => {
    const {dispatch, state} = cm;
    const {to = 0, from = 0} = state.selection.ranges[0];

    const contentSnippet = snippet(phrase);

    cm.focus();

    return contentSnippet({dispatch, state}, null, from, to);
};

const syncCompletion = cm => {
    return new Promise((resolve, reject) => {
        const timeoutId = setTimeout(() => {
            clearInterval(intervalId);
            reject(new Error('Sync completion timeout is over'));
        }, 400);

        const intervalId = setInterval(() => {
            if (completionStatus(cm.state) === 'active' || cm.destroyed) {
                clearInterval(intervalId);
                clearTimeout(timeoutId);
                resolve();
            }
        }, 1);
    });
};

const getCompletions = cm => currentCompletions(cm.state).map(({label}) => label);

const forEachAsync = (array, iterator) => {
    return Promise.all(array.map(item => new Promise(async resolve => {
        await iterator(item);
        resolve();
    })));
};

const expectDiagnostic = (cm, expected) => new Promise(resolve => setTimeout(() => {
    const diagnostics = [];
    forEachDiagnostic(cm.state, diagnostic => diagnostics.push(diagnostic));

    expect(diagnostics).toEqual(expected);

    resolve();
}));

describe('oroform/js/app/views/expression-editor-view', () => {
    beforeEach(() => {
        window.setFixtures(html);
    });

    afterEach(() => {
        expressionEditorView.dispose();
    });

    describe('check initialization', () => {
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
        });

        it('view is defined and instance of BaseView', () =>{
            expect(expressionEditorView).toEqual(jasmine.any(BaseView));
        });

        it('view throw an exception when util options is missed', () => {
            expect(() => {
                const options = createEditorOptions({util: null});
                expressionEditorView = new ExpressionEditorView(options);
            }).toThrowError();
        });
    });

    describe('check autocomplete logic', () =>{
        beforeEach(() =>{
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;
        });

        it('chain select', async () => {
            updateState(cm, 'pro#{1}');

            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.');

            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.brand.');

            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.brand.id ');

            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.brand.id != ');

            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.brand.id != pricelist[].');
        });

        it('check suggested items for "product.category."', async () => {
            updateState(cm, 'product.category.#{1}');
            await syncCompletion(cm);

            expect(currentCompletions(cm.state).map(({label}) => label)).toEqual([
                'id',
                'updatedAt'
            ]);
        });

        it('check suggested items if previous item is entity or scalar(not operation)', async () => {
            const values = ['product.featured', '1', '1 in [1,2,3]', '(1 == 1)'];

            await forEachAsync(values, async value => {
                flushContent(cm);
                updateState(cm, value + ' #{1}');
                startCompletion(cm);
                await syncCompletion(cm);
                expect(getCompletions(cm)).toContain('!=');
            });
        });

        it('check suggested items if previous item is operation', async () => {
            const values = ['', '+', '(1 =='];

            await forEachAsync(values, async value => {
                flushContent(cm);
                updateState(cm, value + ' #{1}');
                startCompletion(cm);
                await syncCompletion(cm);

                expect(getCompletions(cm)).toEqual([
                    'pricelist…',
                    'product…'
                ]);
            });
        });
    });

    describe('check value update after inserting selected value', () => {
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;
        });

        it('inserting in the field start', async () => {
            flushContent(cm);
            updateState(cm, 'pr#{1}o');
            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.');

            flushContent(cm);
            updateState(cm, 'product.#{1} == 10');
            startCompletion(cm);
            await syncCompletion(cm);
            moveCompletionForward(cm);
            moveCompletionForward(cm);
            moveCompletionForward(cm);
            moveCompletionForward(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.brand. == 10');

            flushContent(cm);
            updateState(cm, 'product.id !#{1}');
            startCompletion(cm);
            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.id != ');
        });
    });

    describe('check data source render', () => {
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;
        });

        it('shown if type pricel', async () => {
            updateState(cm, 'pricel#{1}');
            await syncCompletion(cm);
            acceptCompletion(cm);
            const $dataSource = expressionEditorView.getDataSource('pricelist').$widget;

            expect($dataSource.is(':visible')).toBeTruthy();

            flushContent(cm);
            updateState(cm, 'pricelist[1].id + product.id#{1}');
            await syncCompletion(cm);

            expect($dataSource.is(':visible')).toBeFalsy();
        });
    });

    describe('when allowed operations configured', () => {
        it('only math operations is accessible', async () => {
            const options = createEditorOptions({
                util: {
                    allowedOperations: ['math'],
                    itemLevelLimit: 2
                }
            });
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;

            updateState(cm, 'pro#{1}');
            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.');

            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.id ');

            await syncCompletion(cm);
            expect(getCompletions(cm)).toContain('+');
            expect(getCompletions(cm)).not.toContain('!=');
            expect(getCompletions(cm)).not.toContain('and');
            expect(getCompletions(cm)).not.toContain('match');
        });

        it('only equality and compare operations are accessible', async () => {
            const options = createEditorOptions({
                util: {
                    allowedOperations: ['equality', 'compare'],
                    itemLevelLimit: 2
                }
            });
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;

            updateState(cm, 'pro#{1}');
            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.');
            await syncCompletion(cm);
            acceptCompletion(cm);
            expect(expressionEditorView.el.value).toEqual('product.id ');

            await syncCompletion(cm);

            expect(getCompletions(cm)).toContain('<');
            expect(getCompletions(cm)).toContain('!=');
            expect(getCompletions(cm)).not.toContain('+');
        });
    });

    describe('check editor syntax', () => {
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;
        });

        it('nodes', () => {
            updateState(cm, `(product.id = 1 and product.sku matches containsRegExp("test") or 
            product.sku matches containsRegExp("skus")) 
            and product.newArrival = true or product.id in [1, 2, 3]`);

            const arr = [];
            syntaxTree(cm.state).cursor().iterate(node => arr.push(node.name));

            expect(arr).toEqual([
                'SymfonyExpression', 'BracketGroup', 'BracketOpen', 'GroupNode',
                'Entity', 'EntityName', 'Dot', 'PropertyName', 'GroupNode', 'Operator', 'GroupNode',
                'Number', 'GroupNode', 'GroupOperator', 'GroupNode', 'Entity', 'EntityName', 'Dot',
                'PropertyName', 'GroupNode', 'LiteralOperator', 'LiteralOperator', 'GroupNode',
                'Function', 'FunctionName', 'BracketOpen', 'ArgList', 'String', 'BracketClose',
                'GroupNode', 'GroupOperator', 'GroupNode', 'Entity', 'EntityName', 'Dot',
                'PropertyName', 'GroupNode', 'LiteralOperator', 'LiteralOperator', 'GroupNode',
                'Function', 'FunctionName', 'BracketOpen', 'ArgList', 'String', 'BracketClose',
                'BracketClose', 'GroupNode', 'GroupOperator', 'GroupNode', 'Entity', 'EntityName',
                'Dot', 'PropertyName', 'GroupNode', 'Operator', 'GroupNode', 'Boolean', 'GroupNode',
                'GroupOperator', 'GroupNode', 'Entity', 'EntityName', 'Dot', 'PropertyName', 'GroupNode',
                'LiteralOperator', 'LiteralOperator', 'GroupNode', 'Array', 'SquareBracketOpen',
                'ArrayItem', 'Number', 'Comma', 'ArrayItem', 'Number', 'Comma', 'ArrayItem',
                'Number', 'SquareBracketClose'
            ]);
        });
    });

    describe('check editor side panel', () => {
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;
        });

        it('is defined', () => {
            expect(cm.dom.querySelector('.cm-panel-buttons')).toBeInDOM();
        });

        it('click on button', () => {
            const expectedResults = {
                'equals': '== ',
                'more_than': '> ',
                'equals_greater_than': '>= ',
                'no_equals': '!= ',
                'less_than': '< ',
                'less_greater_than': '<= ',
                'addition': '+ ',
                'multiplication': '* ',
                'precedence': '% ',
                'subtraction': '- ',
                'division': '/ ',
                'parentheses': '()',
                'in': 'in []',
                'not_in': 'not in []',
                'and': 'and ',
                'or': 'or ',
                'yes': ' == true',
                'no': ' == false',
                'match': 'matches containsRegExp()',
                'does_not_match': 'not matches containsRegExp()',
                'is_empty': ' == 0',
                'is_not_empty': ' != 0',
                'between': ' >=  and  <= ',
                'not_between': ' <  and  > '
            };

            defaultOperations.forEach(operation => {
                flushContent(cm);

                expect(typeof operation.handler).toBe('function');

                operation.handler(cm);

                expect(expressionEditorView.el.value).toEqual(expectedResults[operation.name]);
            });
        });

        it('brakets', () => {
            const values = ['some content', 'some (content)', '(some) (content)'];

            values.forEach(value => {
                flushContent(cm);
                updateState(cm, value);
                cm.dispatch({
                    selection: {
                        anchor: 0,
                        head: cm.state.doc.toString().length
                    }
                });

                const parentheses = defaultOperations.find(({name}) => name === 'parentheses');

                parentheses.handler(cm);

                expect(expressionEditorView.el.value).toEqual(`(${value})`);
            });
        });
    });

    describe('editor linter check', () => {
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            cm = expressionEditorView.editorView;
        });

        it('invalid property paths', async () => {
            updateState(cm, 'prodct.id != 3');

            await expectDiagnostic(cm, [{
                from: 0,
                to: 6,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 7,
                to: 9,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, 'product.parendtCategory.id != 3');
            await expectDiagnostic(cm, [{
                from: 8,
                to: 23,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 24,
                to: 26,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, 'product.parentCategory.id product.createA');

            await expectDiagnostic(cm, [{
                from: 8,
                to: 22,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 23,
                to: 25,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 34,
                to: 41,
                severity: 'error',
                message: jasmine.any(String)
            }]);
        });

        it('invalid array expression', async () => {
            updateState(cm, 'product.id not in [1 2, 3]');

            await expectDiagnostic(cm, [{
                from: 20,
                to: 21,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, 'product.id not in 1, 2, 3]');

            await expectDiagnostic(cm, [{
                from: 18,
                to: 19,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, 'product.id not in [1, 2, 3');

            await expectDiagnostic(cm, [{
                from: 25,
                to: 26,
                severity: 'error',
                message: jasmine.any(String)
            }]);
        });

        it('invalid brakets', async () => {
            updateState(cm, '(product.id( != 3)');
            await expectDiagnostic(cm, [{
                from: 0,
                to: 1,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, '(product.id) != 3)');
            await expectDiagnostic(cm, [{
                from: 17,
                to: 18,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, '(product.id))((( != 3)');
            await expectDiagnostic(cm, [{
                from: 12,
                to: 13,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 13,
                to: 14,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 14,
                to: 15,
                severity: 'error',
                message: jasmine.any(String)
            }]);
        });

        it('non-opened braces', async () => {
            updateState(cm, '{product.id{ != 3}');
            await expectDiagnostic(cm, [{
                from: 0,
                to: 1,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, '{product.id} != 3}');
            await expectDiagnostic(cm, [{
                from: 17,
                to: 18,
                severity: 'error',
                message: jasmine.any(String)
            }]);

            flushContent(cm);
            updateState(cm, '{product.id}}{{{ != 3}');
            await expectDiagnostic(cm, [{
                from: 12,
                to: 13,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 13,
                to: 14,
                severity: 'error',
                message: jasmine.any(String)
            }, {
                from: 14,
                to: 15,
                severity: 'error',
                message: jasmine.any(String)
            }]);
        });
    });
});
