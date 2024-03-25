import ExpressionLanguage from 'oroexpressionlanguage/js/library/expression-language';
import TestProvider from '../Fixture/test-provider';
import ExpressionFunction from 'oroexpressionlanguage/js/library/expression-function';

describe('oroexpressionlanguage/js/library/expression-language', () => {
    let expressionLanguage;

    describe('caching', () => {
        let savedParsedExpressions;
        let parsedExpression;
        let cacheMock;

        beforeEach(() => {
            savedParsedExpressions = {};
            cacheMock = {
                fetch: function(key) {
                    return key in savedParsedExpressions ? savedParsedExpressions[key] : null;
                },
                save: function(key, expression) {
                    savedParsedExpressions[key] = expression;
                }
            };
            spyOn(cacheMock, 'fetch').and.callThrough();
            spyOn(cacheMock, 'save').and.callThrough();
            expressionLanguage = new ExpressionLanguage(cacheMock);
        });

        it('and cached parse', () => {
            parsedExpression = expressionLanguage.parse('1 + 1', []);
            expect(parsedExpression).toEqual(Object.values(savedParsedExpressions)[0]);
            parsedExpression = expressionLanguage.parse('1 + 1', []);
            expect(parsedExpression).toEqual(Object.values(savedParsedExpressions)[0]);

            expect(cacheMock.fetch).toHaveBeenCalledWith('1 + 1//');
            expect(cacheMock.fetch).toHaveBeenCalledTimes(2);
            expect(cacheMock.save).toHaveBeenCalledWith('1 + 1//', parsedExpression);
            expect(cacheMock.save).toHaveBeenCalledTimes(1);
        });

        it('with different names order', () => {
            const expression = 'a + b';
            expressionLanguage.compile(expression, {0: 'a', B: 'b'});
            expressionLanguage.compile(expression, {B: 'b', 0: 'a'});
            expect(cacheMock.fetch).toHaveBeenCalledTimes(2);
            expect(cacheMock.save).toHaveBeenCalledTimes(1);
        });
    });

    describe('function providers', () => {
        beforeEach(() => {
            expressionLanguage = new ExpressionLanguage(null, [new TestProvider()]);
        });

        it('with evaluation', () => {
            expect(expressionLanguage.evaluate('identity("foo")')).toBe('foo');
        });

        it('with compilation', () => {
            expect(expressionLanguage.compile('identity("foo")')).toBe('"foo"');
        });
    });

    describe('short circuit operators evaluate', () => {
        const object = {};
        const cases = [
            ['false and object.foo()', {object: object}, false],
            ['false && object.foo()', {object: object}, false],
            ['true || object.foo()', {object: object}, true],
            ['true or object.foo()', {object: object}, true]
        ];

        beforeEach(() => {
            expressionLanguage = new ExpressionLanguage();
            object.foo = jasmine.createSpy('foo');
        });

        cases.forEach(testCase => {
            it('expression: `' + testCase[0] + '`', () => {
                expect(expressionLanguage.evaluate(testCase[0], testCase[1])).toBe(testCase[2]);
                expect(object.foo).not.toHaveBeenCalled();
            });
        });
    });

    describe('short circuit operators compile', () => {
        const cases = [
            ['false and foo', {foo: 'foo'}, false],
            ['false && foo', {foo: 'foo'}, false],
            ['true || foo', {foo: 'foo'}, true],
            ['true or foo', {foo: 'foo'}, true]
        ];

        beforeEach(() => {
            expressionLanguage = new ExpressionLanguage();
        });

        cases.forEach(testCase => {
            it('expression: `' + testCase[0] + '`', () => {
                const compiled = expressionLanguage.compile(testCase[0], testCase[1]);
                expect(eval(compiled)).toBe(testCase[2]); // eslint-disable-line no-eval
            });
        });
    });

    it('caching for overridden variable names', () => {
        const expression = 'a + b';
        expressionLanguage = new ExpressionLanguage();
        expressionLanguage.evaluate(expression, {a: 1, b: 1});
        expect(expressionLanguage.compile(expression, {0: 'a', B: 'b'})).toBe('(a + B)');
    });

    it('strict equality', () => {
        expressionLanguage = new ExpressionLanguage();
        expect(expressionLanguage.compile('123 === a', ['a'])).toBe('(123 === a)');
    });

    describe('errors handling', () => {
        const laterRegisterMessage =
            'Registering functions after calling evaluate(), compile() or parse() is not supported.';

        beforeEach(() => {
            expressionLanguage = new ExpressionLanguage();
        });

        const cases = [
            ['register function', el => {
                el.register('fn', () => {
                    //
                }, () => {
                    //
                });
            }],
            ['add expression function', el => {
                el.addFunction(new ExpressionFunction('fn', () => {
                    //
                }, () => {
                    //
                }));
            }],
            ['register function provider', el => {
                el.registerProvider(new TestProvider());
            }]
        ];

        describe('register after parse', () => {
            cases.forEach(testCase => {
                it(testCase[0], () => {
                    expressionLanguage.parse('1 + 1', []);
                    expect(testCase[1].bind(null, expressionLanguage)).toThrowError(laterRegisterMessage);
                });
            });
        });

        describe('register after evaluation', () => {
            cases.forEach(testCase => {
                it(testCase[0], () => {
                    expressionLanguage.evaluate('1 + 1');
                    expect(testCase[1].bind(null, expressionLanguage)).toThrowError(laterRegisterMessage);
                });
            });
        });

        describe('register after compilation', () => {
            cases.forEach(testCase => {
                it(testCase[0], () => {
                    expressionLanguage.compile('1 + 1');
                    expect(testCase[1].bind(null, expressionLanguage)).toThrowError(laterRegisterMessage);
                });
            });
        });

        it('call bad callable', () => {
            expect(() => {
                expressionLanguage.evaluate('foo.myfunction()', {foo: {}});
            }).toThrow(new TypeError('Unable to call method "myfunction" of object "Object".'));
        });
    });
});
