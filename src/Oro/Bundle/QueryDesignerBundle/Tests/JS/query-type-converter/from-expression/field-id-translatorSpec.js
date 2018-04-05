define(function(require) {
    'use strict';

    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/from-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var createFunctionNode = ExpressionLanguageLibrary.tools.createFunctionNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/from-expression/field-id-translator', function() {
        var entityStructureDataProviderMock;
        var translator;

        beforeEach(function() {
            entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
                jasmine.createSpy('getPathByRelativePropertyPath').and
                    .callFake(function(relativePropertyPath) {
                        return {
                            'bar': 'bar',
                            'bar.qux': 'bar+Oro\\QuxClassName::qux',
                            'bar.qux.baz': 'bar+Oro\\QuxClassName::qux+Oro\\BazClassName::baz'
                        }[relativePropertyPath];
                    }),
                jasmine.combineSpyObj('rootEntity', [
                    jasmine.createSpy('get').and
                        .callFake(function(attr) {
                            return {
                                alias: 'foo'
                            }[attr];
                        })
                ])
            ]);

            translator = new FieldIdTranslator(entityStructureDataProviderMock);
        });

        it('translate foo.bar AST to fieldId', function() {
            var ast = createGetAttrNode('foo.bar');
            expect(translator.translate(ast)).toEqual('bar');
        });

        it('translate foo.bar.qux AST to fieldId', function() {
            var ast = createGetAttrNode('foo.bar.qux');
            expect(translator.translate(ast)).toEqual('bar+Oro\\QuxClassName::qux');
        });

        it('translate foo.bar.qux.baz AST to fieldId', function() {
            var ast = createGetAttrNode('foo.bar.qux.baz');
            expect(translator.translate(ast)).toEqual('bar+Oro\\QuxClassName::qux+Oro\\BazClassName::baz');
        });

        it('attempt to translate foo().bar, invalid AST', function() {
            var ast = new GetAttrNode(
                createFunctionNode('foo'),
                new ConstantNode('bar'),
                new ArgumentsNode(),
                GetAttrNode.PROPERTY_CALL
            );
            expect(function() {
                translator.translate(ast);
            }).toThrowError(Error);
        });

        it('attempt to translate foo.bar(), invalid AST', function() {
            var ast = new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode('bar'),
                new ArgumentsNode(),
                GetAttrNode.METHOD_CALL
            );
            expect(function() {
                translator.translate(ast);
            }).toThrowError(Error);
        });

        it('attempt to translate foo[\'bar\'].qux, invalid AST', function() {
            var ast = new GetAttrNode(
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('bar'),
                    new ArgumentsNode(),
                    GetAttrNode.ARRAY_CALL
                ),
                new ConstantNode('qux'),
                new ArgumentsNode(),
                GetAttrNode.PROPERTY_CALL
            );
            expect(function() {
                translator.translate(ast);
            }).toThrowError(Error);
        });

        it('attempt to translate quux.bar, unknown variable name', function() {
            var ast = createGetAttrNode('quux.bar');
            expect(function() {
                translator.translate(ast);
            }).toThrowError(Error);
        });

        it('entityStructureDataProvider is required', function() {
            expect(function() {
                new FieldIdTranslator();
            }).toThrowError(TypeError);
        });

        it('rootEntity has to be defined in entityStructureDataProvider', function() {
            entityStructureDataProviderMock.rootEntity = null;
            expect(function() {
                translator.translate(createGetAttrNode('foo.bar'));
            }).toThrowError(Error);
        });

        it('rootEntity of entityStructureDataProvider has to have alias', function() {
            entityStructureDataProviderMock.rootEntity.get.and.returnValue('');
            expect(function() {
                translator.translate(createGetAttrNode('foo.bar'));
            }).toThrowError(Error);
        });
    });
});
