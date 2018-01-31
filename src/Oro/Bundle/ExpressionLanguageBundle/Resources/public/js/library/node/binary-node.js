define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var range = require('oroexpressionlanguage/lib/php-to-js/range');

    /**
     * @param {string} operator
     * @param {Node} left
     * @param {Node} right
     */
    function BinaryNode(operator, left, right) {
        BinaryNode.__super__.constructor.call(this, [left, right], {operator: operator});
    }

    BinaryNode.prototype = Object.create(Node.prototype);
    BinaryNode.__super__ = Node.prototype;

    var REGEXP = /^\/([\w\W]+)\/([a-z]+)?$/i;
    // minified code of range method
    // eslint-disable-next-line max-len
    var rangeFuncRawMin = 'function(r,a){var o,e,n,t=[],h,i=isNaN(r),C=isNaN(a),k="charCodeAt";for(i||C?i&&C?(h=!0,o=r[k](0),e=a[k](0)):(o=i?0:r,e=C?0:a):(o=r,e=a),n=!(o>e);n?o<=e:o>=e;n?++o:--o)t.push(h?String.fromCharCode(o):o);return t}';

    Object.assign(BinaryNode.prototype, {
        constructor: BinaryNode,

        operators: {
            '~': '+',
            'and': '&&',
            'or': '||'
        },

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            var operator = this.attrs.operator;

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
                        .raw('(' + rangeFuncRawMin + ')(')
                        .compile(this.nodes[0])
                        .raw(', ')
                        .compile(this.nodes[1])
                        .raw(')');
                    return;
            }

            if (operator in this.operators) {
                operator = this.operators[operator];
            }

            compiler
                .raw('(')
                .compile(this.nodes[0])
                .raw(' ')
                .raw(operator)
                .raw(' ')
                .compile(this.nodes[1])
                .raw(')');
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            var operator = this.attrs.operator;
            var left = this.nodes[0].evaluate(functions, values);
            var right;

            switch (operator) {
                case 'or':
                case '||':
                    return left || this.nodes[1].evaluate(functions, values);
                case 'and':
                case '&&':
                    return left && this.nodes[1].evaluate(functions, values);
            }

            right = this.nodes[1].evaluate(functions, values);

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
                    var parts = right.match(REGEXP);
                    return (new RegExp(parts[1], parts[2])).test(left);
                case '..':
                    return range(left, right);
            }
        },

        _compileIndexOf: function(compiler) {
            compiler
                .raw('Object.values(')
                .compile(this.nodes[1])
                .raw(').indexOf(')
                .compile(this.nodes[0])
                .raw(')');
        }
    });

    return BinaryNode;
});
