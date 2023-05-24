import FieldIdTranslatorToExpression from 'oroquerydesigner/js/query-type-converter/to-expression/field-id-translator';
import {createGetAttrNode} from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator', () => {
    let entityStructureDataProviderMock;
    let translator;

    beforeEach(() => {
        entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
            jasmine.createSpy('getRelativePropertyPathByPath').and
                .callFake(fieldId => ({'bar+Oro\\QuxClassName::qux': 'bar.qux'}[fieldId])),
            jasmine.combineSpyObj('rootEntity', [
                jasmine.createSpy('get').and.callFake(attr => ({alias: 'foo'}[attr]))
            ])
        ]);

        translator = new FieldIdTranslatorToExpression(entityStructureDataProviderMock);
    });

    it('translate valid fieldId to AST', () => {
        const expectedAST = createGetAttrNode('foo.bar.qux');
        expect(translator.translate('bar+Oro\\QuxClassName::qux')).toEqual(expectedAST);
    });

    it('throws error for empty fieldId', () => {
        expect(() => {
            translator.translate('');
        }).toThrowError(TypeError);
    });

    it('entityStructureDataProvider is required', () => {
        expect(() => {
            new FieldIdTranslatorToExpression();
        }).toThrowError(TypeError);
    });

    it('rootEntity has to be defined in entityStructureDataProvider', () => {
        entityStructureDataProviderMock.rootEntity = null;
        expect(() => {
            translator.translate('bar+Oro\\QuxClassName::qux');
        }).toThrowError(Error);
    });

    it('rootEntity of entityStructureDataProvider has to have alias', () => {
        entityStructureDataProviderMock.rootEntity.get.and.returnValue('');
        expect(() => {
            translator.translate('bar+Oro\\QuxClassName::qux');
        }).toThrowError(Error);
    });
});
