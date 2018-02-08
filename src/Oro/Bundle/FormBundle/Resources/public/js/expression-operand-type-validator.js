define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ASTNodeWrapper = require('oroexpressionlanguage/js/ast-node-wrapper');
    var ConditionalNode = require('oroexpressionlanguage/js/library/node/conditional-node');
    var GetAttrNode = require('oroexpressionlanguage/js/library/node/get-attr-node');
    var NameNode = require('oroexpressionlanguage/js/library/node/name-node');

    /**
     * @typedef {Object} EntityInfo
     * @poperty {boolean} isCollection - says if entity has to be used like collection
     * @poperty {string} name - alias or className of entity
     * @poperty {EntityTreeNode} fields - node of entityTree that contains its fields
     */

    /**
     * @typedef {Object} OperationInfo
     * @poperty {string} item - an operator (e.g. '+', '*')
     * @poperty {string} type - type of operator (e.g. 'math', 'equal')
     */

    /**
     * @param {Object} options
     * @param {Array.<EntityInfo>} options.entities - array with objects that contain information about root entities
     * @param {Object.<string, OperationInfo>} options.operations - keys are strings of operators (e.g. '+', '*')
     * @param {number} options.itemLevelLimit - says how deep in entityTree can be used a field
     * @param {boolean} options.isConditionalNodeAllowed - when false an exeption is thrown if conditional node is used
     */
    function ExpressionOperandTypeValidator(options) {
        _.extend(this, _.pick(options, 'entities', 'itemLevelLimit', 'operations', 'isConditionalNodeAllowed'));
    }

    ExpressionOperandTypeValidator.prototype = {
        constructor: ExpressionOperandTypeValidator,

        /**
         * @type {Array.<EntityInfo>}
         */
        entities: null,

        itemLevelLimit: 3,

        /**
         * @type {Object.<string, OperationInfo>}
         */
        operations: null,

        isConditionalNodeAllowed: true,

        /**
         * Tests a parsed expression.
         *
         * @param {ParsedExpression} parsedExpression - instance of ParsedExpression that created by ExpressionLanguage
         * @throws {TypeError} if the expression contains wrong entity fields or operations
         */
        expectValid: function(parsedExpression) {
            var astNodeWrapper = new ASTNodeWrapper(parsedExpression.getNodes());
            if (!this.isConditionalNodeAllowed) {
                this._expectWithoudConditionals(astNodeWrapper);
            }
            if (this.operations) {
                this._expectAllowedOperators(astNodeWrapper);
            }
            if (this.entities) {
                this._expectValidPropertyPath(astNodeWrapper);
            }
        },

        /**
         * @param {ASTNodeWrapper} astNodeWrapper - instance of ASTNodeWrapper that wraps nodes created by
         *                                          ExpressionLanguage
         * @throws {TypeError} if node tree contains conditionals nodes
         * @protected
         */
        _expectWithoudConditionals: function(astNodeWrapper) {
            if (astNodeWrapper.findInstancesOf(ConditionalNode).length !== 0) {
                throw new TypeError('Forbidden conditional constuction is used in expression.');
            }
        },

        /**
         * @param {ASTNodeWrapper} astNodeWrapper - instance of ASTNodeWrapper that wraps nodes created by
         *                                          ExpressionLanguage
         * @throws {TypeError} if node tree contains forbidden operators
         * @protected
         */
        _expectAllowedOperators: function(astNodeWrapper) {
            var forbiddenOperatorNodes = astNodeWrapper.findAll(function(node) {
                var operator = node.attr('operator');
                return operator !== void 0 && !(operator in this.operations);
            }.bind(this));
            if (forbiddenOperatorNodes.length !== 0) {
                var message;
                var operators = _.uniq(forbiddenOperatorNodes.map(function(node) {
                    return node.attr('operator');
                }));
                if (operators.length === 1) {
                    message = 'Forbidden operator `' + forbiddenOperatorNodes[0].attr('operator') +
                        '` is used in expression.';
                } else {
                    message = 'Forbidden operators `' + operators.join('`, `') + '` are used in expression.';
                }
                throw new TypeError(message);
            }
        },

        /**
         * @param {ASTNodeWrapper} astNodeWrapper - instance of ASTNodeWrapper that wraps nodes created by
         *                                          ExpressionLanguage
         * @throws {TypeError} if node tree contains invalid property paths
         * @protected
         */
        _expectValidPropertyPath: function(astNodeWrapper) {
            this.entities.forEach(function(entity) {
                var nameNodes = astNodeWrapper.findAll(function(node) {
                    return node.instanceOf(NameNode) && node.attr('name') === entity.name;
                });

                nameNodes.forEach(function(node) {
                    var source = entity.name;
                    if (entity.isCollection) {
                        if (node.parent.attr('type') !== GetAttrNode.ARRAY_CALL) {
                            throw new TypeError('Attempt using `' + entity.name + '` collection like a single entity.');
                        }
                        source += '[' + node.parent.child(1).attr('value') + ']';
                        node = node.parent;
                    } else if (node.parent.attr('type') !== GetAttrNode.PROPERTY_CALL) {
                        throw new TypeError('Attempt using `' + entity.name + '` entity like a collection.');
                    }
                    node = node.parent;
                    var fieldName;
                    var level = 1;
                    var field = entity.fields;
                    while (node && node.instanceOf(GetAttrNode)) {
                        level++;
                        fieldName = node.child(1).attr('value');
                        field = field[fieldName];
                        if (!field) {
                            throw new TypeError('Field `' + fieldName + '` isn\'t presented in `' + source + '`.');
                        } else if (level > this.itemLevelLimit) {
                            throw new TypeError('Attempt using `' + fieldName + '` field of `' + source +
                                '` exceeds the deep level limit.');
                        }
                        node = node.parent;
                        source += '.' + fieldName;
                    }
                    if (!field.__isField) {
                        throw new TypeError('The `' + source + '` can\'t be used as field.');
                    }
                }.bind(this));
            }.bind(this));
        }
    };

    return ExpressionOperandTypeValidator;
});
