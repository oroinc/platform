define(function(require) {
    'use strict';

    var ExpressionLanguage = require('oroexpressionlanguage/js/library/expression-language');
    var TestProvider = require('../Fixture/test-provider');
    var ExpressionFunction = require('oroexpressionlanguage/js/library/expression-function');

    describe('oroexpressionlanguage/js/library/expression-language', function() {
        var expressionLanguage;

        describe('caching', function() {
            var savedParsedExpressions;
            var parsedExpression;
            var cacheMock;

            beforeEach(function() {
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

            it('and cached parse', function() {
                parsedExpression = expressionLanguage.parse('1 + 1', []);
                expect(parsedExpression).toEqual(Object.values(savedParsedExpressions)[0]);
                parsedExpression = expressionLanguage.parse('1 + 1', []);
                expect(parsedExpression).toEqual(Object.values(savedParsedExpressions)[0]);

                expect(cacheMock.fetch).toHaveBeenCalledWith('1 + 1//');
                expect(cacheMock.fetch).toHaveBeenCalledTimes(2);
                expect(cacheMock.save).toHaveBeenCalledWith('1 + 1//', parsedExpression);
                expect(cacheMock.save).toHaveBeenCalledTimes(1);
            });

            it('with different names order', function() {
                var expression = 'a + b';
                expressionLanguage.compile(expression, {0: 'a', B: 'b'});
                expressionLanguage.compile(expression, {B: 'b', 0: 'a'});
                expect(cacheMock.fetch).toHaveBeenCalledTimes(2);
                expect(cacheMock.save).toHaveBeenCalledTimes(1);
            });
        });

        describe('function providers', function() {
            beforeEach(function() {
                expressionLanguage = new ExpressionLanguage(null, [new TestProvider()]);
            });

            it('with evaluation', function() {
                expect(expressionLanguage.evaluate('identity("foo")')).toBe('foo');
            });

            it('with compilation', function() {
                expect(expressionLanguage.compile('identity("foo")')).toBe('"foo"');
            });
        });

        describe('short circuit operators evaluate', function() {
            var object = {};
            var cases = [
                ['false and object.foo()', {object: object}, false],
                ['false && object.foo()', {object: object}, false],
                ['true || object.foo()', {object: object}, true],
                ['true or object.foo()', {object: object}, true]
            ];

            beforeEach(function() {
                expressionLanguage = new ExpressionLanguage();
                object.foo = jasmine.createSpy('foo');
            });

            cases.forEach(function(testCase) {
                it('expression: `' + testCase[0] + '`', function() {
                    expect(expressionLanguage.evaluate(testCase[0], testCase[1])).toBe(testCase[2]);
                    expect(object.foo).not.toHaveBeenCalled();
                });
            });
        });

        describe('short circuit operators compile', function() {
            var cases = [
                ['false and foo', {foo: 'foo'}, false],
                ['false && foo', {foo: 'foo'}, false],
                ['true || foo', {foo: 'foo'}, true],
                ['true or foo', {foo: 'foo'}, true]
            ];

            beforeEach(function() {
                expressionLanguage = new ExpressionLanguage();
            });

            cases.forEach(function(testCase) {
                it('expression: `' + testCase[0] + '`', function() {
                    var compiled = expressionLanguage.compile(testCase[0], testCase[1]);
                    expect(eval(compiled)).toBe(testCase[2]); // eslint-disable-line no-eval
                });
            });
        });

        it('caching for overridden variable names', function() {
            var expression = 'a + b';
            expressionLanguage = new ExpressionLanguage();
            expressionLanguage.evaluate(expression, {a: 1, b: 1});
            expect(expressionLanguage.compile(expression, {0: 'a', B: 'b'})).toBe('(a + B)');
        });

        it('strict equality', function() {
            expressionLanguage = new ExpressionLanguage();
            expect(expressionLanguage.compile('123 === a', ['a'])).toBe('(123 === a)');
        });

        describe('errors handling', function() {
            var laterRegisterMessage =
                'Registering functions after calling evaluate(), compile() or parse() is not supported.';

            beforeEach(function() {
                expressionLanguage = new ExpressionLanguage();
            });

            var cases = [
                ['register function', function(el) {
                    el.register('fn', function() {
                        //
                    }, function() {
                        //
                    });
                }],
                ['add expression function', function(el) {
                    el.addFunction(new ExpressionFunction('fn', function() {
                        //
                    }, function() {
                        //
                    }));
                }],
                ['register function provider', function(el) {
                    el.registerProvider(new TestProvider());
                }]
            ];

            describe('register after parse', function() {
                cases.forEach(function(testCase) {
                    it(testCase[0], function() {
                        expressionLanguage.parse('1 + 1', []);
                        expect(testCase[1].bind(null, expressionLanguage)).toThrowError(laterRegisterMessage);
                    });
                });
            });

            describe('register after evaluation', function() {
                cases.forEach(function(testCase) {
                    it(testCase[0], function() {
                        expressionLanguage.evaluate('1 + 1');
                        expect(testCase[1].bind(null, expressionLanguage)).toThrowError(laterRegisterMessage);
                    });
                });
            });

            describe('register after compilation', function() {
                cases.forEach(function(testCase) {
                    it(testCase[0], function() {
                        expressionLanguage.compile('1 + 1');
                        expect(testCase[1].bind(null, expressionLanguage)).toThrowError(laterRegisterMessage);
                    });
                });
            });

            it('call bad callable', function() {
                expect(function() {
                    expressionLanguage.evaluate('foo.myfunction()', {foo: {}});
                }).toThrow(new TypeError('Unable to call method "myfunction" of object "Object".'));
            });
        });
    });
});
