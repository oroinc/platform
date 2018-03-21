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
        var translator;

        beforeEach(function() {
            providerMock = {
                getRelativePropertyPathByPath: jasmine.createSpy('getRelativePropertyPathByPath').and
                    .callFake(function(fieldId) {
                        return {
                            'bar+Oro\\QuxClassName::qux': 'bar.qux'
                        }[fieldId];
                    }),
                rootEntity: {
                    get: jasmine.createSpy('get').and
                        .callFake(function(attr) {
                            return {
                                alias: 'foo'
                            }[attr];
                        })
                }
            };

            translator = new FieldIdTranslator(providerMock);
        });

        it('translate valid fieldId to AST', function() {
            var expectedAST = new GetAttrNode(
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('bar'),
                    new ArgumentsNode(),
                    GetAttrNode.PROPERTY_CALL
                ),
                new ConstantNode('qux'),
                new ArgumentsNode(),
                GetAttrNode.PROPERTY_CALL
            );
            expect(translator.translate('bar+Oro\\QuxClassName::qux')).toEqual(expectedAST);
        });

        it('throws error for empty fieldId', function() {
            expect(function() {
                translator.translate('');
            }).toThrowError(TypeError);
        });

        it('entityStructureDataProvider is required', function() {
            expect(function() {
                new FieldIdTranslator();
            }).toThrowError(TypeError);
        });

        it('rootEntity has to be defined in entityStructureDataProvider', function() {
            providerMock.rootEntity = null;
            expect(function() {
                translator.translate('bar+Oro\\QuxClassName::qux');
            }).toThrowError(Error);
        });

        it('rootEntity of entityStructureDataProvider has to have alias', function() {
            providerMock.rootEntity = {
                get: jasmine.createSpy('get').and.callFake(function(attr) {
                    return {
                        alias: ''
                    }[attr];
                })
            };
            expect(function() {
                translator.translate('bar+Oro\\QuxClassName::qux');
            }).toThrowError(Error);
        });
    });
});
