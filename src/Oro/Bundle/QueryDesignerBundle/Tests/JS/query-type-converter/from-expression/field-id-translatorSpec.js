import FieldIdTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/field-id-translator';
import {ArgumentsNode, ConstantNode, GetAttrNode, NameNode, tools}
    from 'oroexpressionlanguage/js/expression-language-library';
import 'lib/jasmine-oro';

const {createFunctionNode, createGetAttrNode} = tools;

describe('oroquerydesigner/js/query-type-converter/from-expression/field-id-translator', () => {
    let entityStructureDataProviderMock;
    let translator;

    beforeEach(() => {
        entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
            jasmine.createSpy('getPathByRelativePropertyPath').and
                .callFake(relativePropertyPath => {
                    return {
                        'bar': 'bar',
                        'bar.qux': 'bar+Oro\\QuxClassName::qux',
                        'bar.qux.baz': 'bar+Oro\\QuxClassName::qux+Oro\\BazClassName::baz'
                    }[relativePropertyPath];
                }),
            jasmine.combineSpyObj('rootEntity', [
                jasmine.createSpy('get').and
                    .callFake(attr => ({alias: 'foo'}[attr]))
            ])
        ]);

        translator = new FieldIdTranslatorFromExpression(entityStructureDataProviderMock);
    });

    it('translate foo.bar AST to fieldId', () => {
        const ast = createGetAttrNode('foo.bar');
        expect(translator.translate(ast)).toEqual('bar');
    });

    it('translate foo.bar.qux AST to fieldId', () => {
        const ast = createGetAttrNode('foo.bar.qux');
        expect(translator.translate(ast)).toEqual('bar+Oro\\QuxClassName::qux');
    });

    it('translate foo.bar.qux.baz AST to fieldId', () => {
        const ast = createGetAttrNode('foo.bar.qux.baz');
        expect(translator.translate(ast)).toEqual('bar+Oro\\QuxClassName::qux+Oro\\BazClassName::baz');
    });

    it('attempt to translate foo().bar, invalid AST', () => {
        const ast = new GetAttrNode(
            createFunctionNode('foo'),
            new ConstantNode('bar'),
            new ArgumentsNode(),
            GetAttrNode.PROPERTY_CALL
        );
        expect(() => {
            translator.translate(ast);
        }).toThrowError(Error);
    });

    it('attempt to translate foo.bar(), invalid AST', () => {
        const ast = new GetAttrNode(
            new NameNode('foo'),
            new ConstantNode('bar'),
            new ArgumentsNode(),
            GetAttrNode.METHOD_CALL
        );
        expect(() => {
            translator.translate(ast);
        }).toThrowError(Error);
    });

    it('attempt to translate foo[\'bar\'].qux, invalid AST', () => {
        const ast = new GetAttrNode(
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
        expect(() => {
            translator.translate(ast);
        }).toThrowError(Error);
    });

    it('attempt to translate quux.bar, unknown variable name', () => {
        const ast = createGetAttrNode('quux.bar');
        expect(() => {
            translator.translate(ast);
        }).toThrowError(Error);
    });

    it('entityStructureDataProvider is required', () => {
        expect(() => {
            new FieldIdTranslatorFromExpression();
        }).toThrowError(TypeError);
    });

    it('rootEntity has to be defined in entityStructureDataProvider', () => {
        entityStructureDataProviderMock.rootEntity = null;
        expect(() => {
            translator.translate(createGetAttrNode('foo.bar'));
        }).toThrowError(Error);
    });

    it('rootEntity of entityStructureDataProvider has to have alias', () => {
        entityStructureDataProviderMock.rootEntity.get.and.returnValue('');
        expect(() => {
            translator.translate(createGetAttrNode('foo.bar'));
        }).toThrowError(Error);
    });
});
