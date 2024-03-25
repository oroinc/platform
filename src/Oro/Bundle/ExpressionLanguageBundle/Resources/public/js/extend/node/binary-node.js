import OriginalBinaryNode from 'oroexpressionlanguage/js/library/node/binary-node';

class BinaryNode extends OriginalBinaryNode {
    /**
     * @inheritDoc
     */
    compile(compiler) {
        const operator = this.attrs.operator;
        const equalOperators = {
            '=': '===',
            '!=': '!=='
        };

        if (operator in equalOperators) {
            compiler
                .raw('(')
                .compile(this.nodes[0])
                .raw(' ')
                .raw(equalOperators[operator])
                .raw(' ')
                .compile(this.nodes[1])
                .raw(')');
        } else {
            super.compile(compiler);
        }
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        let left;
        let right;
        const operator = this.attrs.operator;

        if (['=', '!='].indexOf(operator) !== -1) {
            left = this.nodes[0].evaluate(functions, values);
            right = this.nodes[1].evaluate(functions, values);
            switch (operator) {
                case '=':
                    return left === right;
                case '!=':
                    return left !== right;
            }
        } else {
            return super.evaluate(functions, values);
        }
    }
}

export default BinaryNode;
