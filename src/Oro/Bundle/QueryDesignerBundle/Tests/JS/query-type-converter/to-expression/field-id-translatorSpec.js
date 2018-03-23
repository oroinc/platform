define(function(require) {
    'use strict';

    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator', function() {
        var entityStructureDataProviderMock;
        var translator;

        beforeEach(function() {
            entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
                jasmine.createSpy('getRelativePropertyPathByPath').and
                    .callFake(function(fieldId) {
                        return {
                            'bar+Oro\\QuxClassName::qux': 'bar.qux'
                        }[fieldId];
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
            entityStructureDataProviderMock.rootEntity = null;
            expect(function() {
                translator.translate('bar+Oro\\QuxClassName::qux');
            }).toThrowError(Error);
        });

        it('rootEntity of entityStructureDataProvider has to have alias', function() {
            entityStructureDataProviderMock.rootEntity.get.and.returnValue('');
            expect(function() {
                translator.translate('bar+Oro\\QuxClassName::qux');
            }).toThrowError(Error);
        });
    });
});
