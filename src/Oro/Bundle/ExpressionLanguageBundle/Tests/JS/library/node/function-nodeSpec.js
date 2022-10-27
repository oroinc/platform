import FunctionNode from 'oroexpressionlanguage/js/library/node/function-node';
import Node from 'oroexpressionlanguage/js/library/node/node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/function-node', () => {
    let node;
    let functions;

    beforeEach(() => {
        node = new FunctionNode('foo', new Node([new ConstantNode('bar')]));
        functions = {
            foo: {
                compiler(arg) {
                    return 'foo(' + arg + ')';
                },
                evaluator(variables, arg) {
                    return arg;
                }
            }
        };
    });

    it('evaluation', () => {
        expect(node.evaluate(functions, {})).toBe('bar');
    });

    it('compilation', function() {
        const compiler = new Compiler(functions);
        node.compile(compiler);
        expect(compiler.getSource()).toBe('foo("bar")');
    });
});
