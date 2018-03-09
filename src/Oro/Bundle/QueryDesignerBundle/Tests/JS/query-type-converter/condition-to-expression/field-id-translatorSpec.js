define(function(require) {
    'use strict';

    var FieldIdTranslator =
        require('oroquerydesigner/js/query-type-converter/condition-to-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;

    describe('oroquerydesigner/js/query-type-converter/condition-to-expression/field-id-translator', function() {
        var providerMock;

        beforeEach(function() {
            providerMock = {
                getRelativePropertyPathByPath:
                    jasmine.createSpy('getRelativePropertyPathByPath').and.returnValue('bar.qux')
            };
        });

        describe('properly configured fieldIdTranslator', function() {
            var translator;

            beforeEach(function() {
                providerMock.rootEntity = {
                    get: jasmine.createSpy('get').and.returnValue('foo')
                };
                translator = new FieldIdTranslator(providerMock);
            });

            it('translate valid fieldId to AST', function() {
                var AST = translator.translate('bar+Oro\\ClassName::qux');
                expect(AST).toEqual(
                    new GetAttrNode(
                        new GetAttrNode(
                            new NameNode('foo'),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        new ConstantNode('qux'),
                        new ArgumentsNode(),
                        GetAttrNode.PROPERTY_CALL
                    )
                );
            });

            it('throws error for empty fieldId', function() {
                expect(function() {
                    translator.translate('');
                }).toThrowError(TypeError);
            });
        });

        it('entityStructureDataProvider is required', function() {
            expect(function() {
                new FieldIdTranslator();
            }).toThrowError(TypeError);
        });

        it('rootEntity has to be defined in entityStructureDataProvider', function() {
            var translator = new FieldIdTranslator(providerMock);
            expect(function() {
                translator.translate('bar+Oro\\ClassName::qux');
            }).toThrowError(Error);
        });

        it('rootEntity of entityStructureDataProvider has to have alias', function() {
            var translator = new FieldIdTranslator(providerMock);
            providerMock.rootEntity = {
                get: jasmine.createSpy('get').and.returnValue('')
            };
            expect(function() {
                translator.translate('bar+Oro\\ClassName::qux');
            }).toThrowError(Error);
        });
    });
});
