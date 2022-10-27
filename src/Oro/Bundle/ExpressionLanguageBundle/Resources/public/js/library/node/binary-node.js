import Node from './node';
import ConstantNode from './constant-node';
import range from 'oroexpressionlanguage/lib/php-to-js/range';

const REGEXP = /^\/([\w\W]+)\/([a-z]+)?$/i;
// minified code of range method
// eslint-disable-next-line max-len
const rangeFuncRawMin = 'function(r,a){var o,e,n,t=[],h,i=isNaN(r),C=isNaN(a),k="charCodeAt";for(i||C?i&&C?(h=!0,o=r[k](0),e=a[k](0)):(o=i?0:r,e=C?0:a):(o=r,e=a),n=!(o>e);n?o<=e:o>=e;n?++o:--o)t.push(h?String.fromCharCode(o):o);return t}';

const operatorsMap = {
    '~': '+',
    'and': '&&',
    'or': '||'
};

class BinaryNode extends Node {
    /**
     * @param {string} operator
     * @param {Node} left
     * @param {Node} right
     */
    constructor(operator, left, right) {
        super([left, right], {operator: operator});
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        let operator = this.attrs.operator;

        switch (operator) {
            case 'matches':
                if (
                    this.nodes[1] instanceof ConstantNode &&
                    typeof this.nodes[1].attrs.value === 'string' &&
                    REGEXP.test(this.nodes[1].attrs.value)
                ) {
                    // since it is a string constant, it is treated as regexp only in case it goes after `matches`
                    // ("foo" matches "/o+/i")
                    compiler.raw(this.nodes[1].attrs.value);
                } else {
                    compiler.compile(this.nodes[1]);
                }
                compiler
                    .raw('.test(')
                    .compile(this.nodes[0])
                    .raw(')');
                return;
            case 'in':
                compiler.raw('(');
                this._compileIndexOf(compiler);
                compiler.raw(' !== -1)');
                return;
            case 'not in':
                compiler.raw('(');
                this._compileIndexOf(compiler);
                compiler.raw(' === -1)');
                return;
            case '**':
                compiler
                    .raw('Math.pow(')
                    .compile(this.nodes[0])
                    .raw(', ')
                    .compile(this.nodes[1])
                    .raw(')');
                return;
            case '..':
                compiler
                    .raw(`(${rangeFuncRawMin})(`)
                    .compile(this.nodes[0])
                    .raw(', ')
                    .compile(this.nodes[1])
                    .raw(')');
                return;
        }

        if (operator in operatorsMap) {
            operator = operatorsMap[operator];
        }

        compiler
            .raw('(')
            .compile(this.nodes[0])
            .raw(' ')
            .raw(operator)
            .raw(' ')
            .compile(this.nodes[1])
            .raw(')');
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        const operator = this.attrs.operator;
        const left = this.nodes[0].evaluate(functions, values);

        switch (operator) {
            case 'or':
            case '||':
                return left || this.nodes[1].evaluate(functions, values);
            case 'and':
            case '&&':
                return left && this.nodes[1].evaluate(functions, values);
        }

        const right = this.nodes[1].evaluate(functions, values);

        switch (operator) {
            case '|':
                return left | right;
            case '^':
                return left ^ right;
            case '&':
                return left & right;
            case '==':
                return left == right; // eslint-disable-line eqeqeq
            case '===':
                return left === right;
            case '!=':
                return left != right; // eslint-disable-line eqeqeq
            case '!==':
                return left !== right;
            case '<':
                return left < right;
            case '>':
                return left > right;
            case '>=':
                return left >= right;
            case '<=':
                return left <= right;
            case 'not in':
                return Object.values(right).indexOf(left) === -1;
            case 'in':
                return Object.values(right).indexOf(left) !== -1;
            case '+':
                return left + right;
            case '-':
                return left - right;
            case '~':
                return left + right;
            case '*':
                return left * right;
            case '/':
                return left / right;
            case '%':
                return left % right;
            case '**':
                return Math.pow(left, right);
            case 'matches':
                const parts = right.match(REGEXP);
                return (new RegExp(parts[1], parts[2])).test(left);
            case '..':
                return range(left, right);
        }
    }

    _compileIndexOf(compiler) {
        compiler
            .raw('Object.values(')
            .compile(this.nodes[1])
            .raw(').indexOf(')
            .compile(this.nodes[0])
            .raw(')');
    }
}

export default BinaryNode;
