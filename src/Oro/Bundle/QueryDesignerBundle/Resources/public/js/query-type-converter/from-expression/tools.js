import {BinaryNode, FunctionNode, GetAttrNode} from 'oroexpressionlanguage/js/expression-language-library';
import {compareAST} from 'oroexpressionlanguage/js/expression-language-tools';

/**
 * Tries to fetch getAttrNode from piece of AST that represent a condition
 *
 * E.g. fetch AST of `account.createdAt` from AST of condition
 *   `account.createdAt >= "2023-05-16 00:00"`
 *   `account.createdAt >= "2023-05-16 00:00" and account.createdAt <= "2023-05-31 03:00"`
 *   `month(account.createdAt) >= "5"`
 *   `year(account.createdAt) >= "1991" and year(account.createdAt) <= currentYear()`
 *
 * @param {Node} node ExpressionLanguage AST node
 * @return {Node|null} node
 * @protected
 */
function resolveFieldAST(node) {
    let getAttrNode = null;
    if (node instanceof BinaryNode) {
        const leftNode = node.nodes[0] instanceof BinaryNode ? node.nodes[0].nodes[0] : node.nodes[0];
        const _node = leftNode instanceof FunctionNode ? leftNode.nodes[0].nodes[0] : leftNode;
        if (_node instanceof GetAttrNode) {
            getAttrNode = _node;
        }
    }

    return getAttrNode;
}

const INVERTIBLE_OPERATOR_MAP = {
    '==': '==',
    '=': '=',
    '!=': '!=',
    '>': '<',
    '<': '>',
    '<=': '>=',
    '>=': '<='
};

/**
 * Swaps nodes places in BinaryNode, if fieldAST is not found on left side and the operation invertible
 *
 * Examples
 *   `"5" != month(account.createdAt)`
 *   converts to
 *   `month(account.createdAt) != "5"`
 *
 *   `"2023-05-16 00:00" <= account.createdAt`
 *   converts to
 *   `account.createdAt >= "2023-05-16 00:00"`
 *
 * @param {Node} node
 * @returns {Node}
 */
function normalizeFieldConditionAST(node) {
    if (
        node instanceof BinaryNode &&
        INVERTIBLE_OPERATOR_MAP.hasOwnProperty(node.attrs.operator) &&
        // could not find field related node on left side of operation
        !resolveFieldAST(node)
    ) {
        const [leftNode, rightNode] = node.nodes;
        const oppositeOperator = INVERTIBLE_OPERATOR_MAP[node.attrs.operator];
        const _node = new BinaryNode(oppositeOperator, rightNode, leftNode);
        if (resolveFieldAST(_node)) {
            node = _node;
        }
    }

    return node;
}

/**
 * Normalizes AST for `between` and `not between` condition
 *
 * Examples
 *   `account.createdAt <= "2023-05-31 03:00" and account.createdAt >= "2023-05-16 00:00"`
 *   or
 *   `"2023-05-31 03:00" >= account.createdAt and account.createdAt >= "2023-05-16 00:00"`
 *   or
 *   `"2023-05-31 03:00" >= account.createdAt and "2023-05-16 00:00" <= account.createdAt`
 *
 *   converts to
 *   `account.createdAt >= "2023-05-16 00:00" and account.createdAt <= "2023-05-31 03:00"`
 *
 * @param {Node} node
 * @returns {Node}
 */
function normalizeBoundaryConditionAST(node) {
    if (node instanceof BinaryNode && node.attrs.operator === 'and') {
        const [_leftNode, _rightNode] = node.nodes;
        const leftNode = normalizeFieldConditionAST(_leftNode);
        const rightNode = normalizeFieldConditionAST(_rightNode);

        if (
            // both sub-operations are done for the same field
            compareAST(resolveFieldAST(leftNode), resolveFieldAST(rightNode)) &&
            // pair of `between` or `not between` operations
            ['<=>=', '<>'].indexOf([leftNode.attrs.operator, rightNode.attrs.operator].sort().join('')) !== -1
        ) {
            if (
                // incorrect sequence of `between` and `not between` operation
                leftNode.attrs.operator === '<=' && rightNode.attrs.operator === '>=' ||
                leftNode.attrs.operator === '>' && rightNode.attrs.operator === '<'
            ) {
                node = new BinaryNode(node.attrs.operator, rightNode, leftNode);
            } else if (
                // field condition AST was normalized
                _leftNode !== leftNode || _rightNode !== rightNode
            ) {
                node = new BinaryNode(node.attrs.operator, leftNode, rightNode);
            }
        }
    }

    return node;
}

export {
    resolveFieldAST,
    normalizeFieldConditionAST,
    normalizeBoundaryConditionAST,
    INVERTIBLE_OPERATOR_MAP
};
