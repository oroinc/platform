define(function(require) {
    'use strict';

    var ExpressionEditorUtil;
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');
    var ExpressionLanguage = require('oroexpressionlanguage/js/extend/expression-language');
    var ExpressionOperandTypeValidator = require('oroform/js/expression-operand-type-validator');

    ExpressionEditorUtil = BaseClass.extend({
        /**
         * RegEx, used for expression analyze, autocomplete and validate
         */
        regex: {
            itemPartBeforeCursor: /^.*[ ]/g, // 1 == prod|uct.id and ... => prod
            itemPartAfterCursor: /[ ].*$/g, // 1 == prod|uct.id and ... => uct.id
            cutDataSourceId: /\[(.*?)\]/g // dataSource[1] => "1", dataSource[] => ""
        },

        /**
         * Constants, used for expression build
         */
        strings: {
            childSeparator: '.',
            itemSeparator: ' '
        },

        /**
         * Default options
         */
        defaultOptions: {
            itemLevelLimit: 3,
            allowedOperations: ['math', 'bool', 'equality', 'compare', 'inclusion', 'like'],
            operations: {
                math: ['+', '-', '%', '*', '/'],
                bool: ['and', 'or'],
                equality: ['==', '!='],
                compare: ['>', '<', '<=', '>='],
                inclusion: ['in', 'not in'],
                like: ['matches']
            },
            rootEntities: []
        },

        /**
         * Generated list of operations autocomplete items
         */
        operationsItems: null,

        /**
         * Generated list of available data sources
         */
        dataSourceNames: null,

        /**
         * Instance of ExpressionLanguage that can parse an expression
         */
        expressionLanguage: null,

        /**
         * Instance of EntityStructureDataProvider that contains data about entity fields
         */
        entityDataProvider: null,

        /**
         * Instance of ExpressionOperandTypeValidator that can check allowed operations, type of operands, and presence
         * used fields in an entity structure
         */
        expressionOperandTypeValidator: null,

        constructor: function ExpressionEditorUtil(options) {
            ExpressionEditorUtil.__super__.constructor.call(this, options);
        },

        /**
         * Initialize util
         *
         * @param {Object} options
         */
        initialize: function(options) {
            if (!options.entityDataProvider) {
                throw new Error('Option "entityDataProvider" is required');
            }
            if ('itemLevelLimit' in options && (!_.isNumber(options.itemLevelLimit) || options.itemLevelLimit < 2)) {
                throw new Error('Option "itemLevelLimit" can\'t be smaller than 2');
            }
            _.extend(this, _.pick(options, 'entityDataProvider', 'dataSourceNames'));
            this.options = _.defaults(_.pick(options, _.keys(this.defaultOptions)), this.defaultOptions);
            this.expressionLanguage = new ExpressionLanguage();
            this._prepareOperationsItems();
            var entities = this.options.rootEntities.map(function(entityName) {
                return {
                    isCollection: this.dataSourceNames.indexOf(entityName) !== -1,
                    name: entityName,
                    fields: this.entityDataProvider.getEntityTreeNodeByPropertyPath(entityName)
                };
            }.bind(this));
            var validatorOptions = {
                entities: entities,
                itemLevelLimit: this.options.itemLevelLimit,
                operations: this.operationsItems,
                isConditionalNodeAllowed: false
            };
            this.expressionOperandTypeValidator = new ExpressionOperandTypeValidator(validatorOptions);
            ExpressionEditorUtil.__super__.initialize.call(this, options);
        },

        /**
         * Validate expression syntax
         *
         * @param {string} expression
         * @return {boolean}
         */
        validate: function(expression) {
            var isValid = true;
            try {
                var parsedExpression = this.expressionLanguage.parse(expression, this.options.rootEntities);
                this.expressionOperandTypeValidator.expectValid(parsedExpression);
            } catch (ex) {
                isValid = false;
            }

            return isValid;
        },

        /**
         * Build autocomplete data by expression and cursor position
         *
         * @param {string} expression
         * @param {integer} position
         * @return {Object}
         */
        getAutocompleteData: function(expression, position) {
            return this._prepareAutocompleteData(expression, position);
        },

        /**
         * Update autocomplete expression by item parts
         *
         * @param {Object} autocompleteData
         * @private
         */
        _updateAutocompleteExpression: function(autocompleteData) {
            autocompleteData.expression = autocompleteData.beforeItem +
                autocompleteData.itemChild.join(this.strings.childSeparator) +
                autocompleteData.afterItem;
        },

        /**
         * Insert into autocomplete data new item
         *
         * @param {Object} autocompleteData
         * @param {string} item
         */
        updateAutocompleteItem: function(autocompleteData, item) {
            var positionModifier = 0;
            if (autocompleteData.itemsType === 'entities') {
                var hasChildren = autocompleteData.items[item].hasChildren;
                if (this.dataSourceNames.indexOf(item) !== -1) {
                    item += '[]';
                    positionModifier = hasChildren ? -2 : -1;
                }

                item += hasChildren ? this.strings.childSeparator : this.strings.itemSeparator;
            } else {
                item += this.strings.itemSeparator;
            }

            autocompleteData.itemChild[autocompleteData.itemLastChildIndex] = item;

            this._updateAutocompleteExpression(autocompleteData);

            autocompleteData.position = autocompleteData.expression.length - autocompleteData.afterItem.length +
                positionModifier;
        },

        /**
         * Set new data source value into autocomplete data
         *
         * @param {Object} autocompleteData
         * @param {string} dataSourceValue
         */
        updateDataSourceValue: function(autocompleteData, dataSourceValue) {
            autocompleteData.itemChild[autocompleteData.itemCursorIndex] = autocompleteData
                .itemCursorChild.replace(this.regex.cutDataSourceId, '[' + dataSourceValue + ']');

            this._updateAutocompleteExpression(autocompleteData);

            autocompleteData.position = autocompleteData.expression.length - autocompleteData.afterItem.length;
        },

        /**
         * Generate list of operations autocomplete items from initialize options
         *
         * @private
         */
        _prepareOperationsItems: function() {
            this.operationsItems = {};
            _.each(this.options.allowedOperations, function(type) {
                _.each(this.options.operations[type], function(item) {
                    this.operationsItems[item] = {
                        type: type,
                        item: item
                    };
                }, this);
            }, this);
        },

        /**
         * Create autocomplete data object
         *
         * @param {String} expression
         * @param {Integer} position
         * @return {Object}
         */
        _prepareAutocompleteData: function(expression, position) {
            var autocompleteData = {
                expression: expression, // full expression
                position: position, // cursor position
                item: '', // item under cursor or just "item"
                beforeItem: '', // part of expression before item
                afterItem: '', // part of expression after item
                itemChild: [], // child of item
                itemLastChildIndex: 0, // index of last child of item
                itemLastChild: '', // last child of item
                itemCursorIndex: 0, // index of an item child under cursor
                itemCursorChild: '', // item child under cursor
                itemsType: '', // `entities` or `operations`
                items: {}, // list of items for autocomplete
                dataSourceKey: '', // key of data source if item is data source
                dataSourceValue: ''// value of data source if item is data source
            };

            this._setAutocompleteItem(autocompleteData);
            this._setAutocompleteItemsType(autocompleteData);
            this._setAutocompleteItems(autocompleteData);
            this._setAutocompleteDataSource(autocompleteData);

            return autocompleteData;
        },

        /**
         * Set item info into autocomplete data
         *
         * @param {Object} autocompleteData
         * @private
         */
        _setAutocompleteItem: function(autocompleteData) {
            var beforeCaret = autocompleteData.expression.slice(0, autocompleteData.position);
            var afterCaret = autocompleteData.expression.slice(autocompleteData.position);

            var itemBeforeCursor = beforeCaret.replace(this.regex.itemPartBeforeCursor, '');
            var itemAfterCursor = afterCaret.replace(this.regex.itemPartAfterCursor, '');

            autocompleteData.beforeItem = beforeCaret.slice(0, beforeCaret.length - itemBeforeCursor.length);
            autocompleteData.afterItem = afterCaret.slice(itemAfterCursor.length);

            autocompleteData.item = itemBeforeCursor + itemAfterCursor;
            autocompleteData.itemChild = autocompleteData.item.split(this.strings.childSeparator);
            autocompleteData.itemLastChildIndex = autocompleteData.itemChild.length - 1;
            autocompleteData.itemLastChild = autocompleteData.itemChild[autocompleteData.itemLastChildIndex];
            autocompleteData.itemCursorIndex = itemBeforeCursor.split(this.strings.childSeparator).length - 1;
            autocompleteData.itemCursorChild = itemBeforeCursor.split(this.strings.childSeparator).pop() +
                itemAfterCursor.split(this.strings.childSeparator).shift();
        },

        /**
         * Set group info into autocomplete data
         *
         * @param {Object} autocompleteData
         * @private
         */
        _setAutocompleteItemsType: function(autocompleteData) {
            var prevItem = _.trim(autocompleteData.beforeItem).split(this.strings.itemSeparator).pop();

            if (!prevItem || prevItem === '(' || prevItem in this.operationsItems) {
                autocompleteData.itemsType = 'entities';
            } else {
                autocompleteData.itemsType = 'operations';
            }
        },

        /**
         * Set autocomplete items into autocomplete data
         *
         * @param {Object} autocompleteData
         * @private
         */
        _setAutocompleteItems: function(autocompleteData) {
            if (autocompleteData.itemsType === 'entities') {
                autocompleteData.items = {};
                if (autocompleteData.itemChild.length > 1) {
                    var parts = autocompleteData.itemChild.map(function(item) {
                        return item.replace(this.regex.cutDataSourceId, '');
                    }.bind(this));
                    if (this.options.rootEntities.indexOf(parts[0]) === -1) {
                        return;
                    }
                    var omitRelationFields = this.options.itemLevelLimit <= parts.length;
                    parts.pop();
                    var levelLimit = this.options.itemLevelLimit - parts.length - 1;
                    var treeNode = this.entityDataProvider
                        .getEntityTreeNodeByPropertyPath(parts.join(this.strings.childSeparator));
                    if (treeNode && treeNode.__isEntity) {
                        _.each(treeNode, function(node) {
                            var isEntity = node.__isEntity;
                            if (isEntity && (omitRelationFields || !node.__hasScalarFieldsInSubtree(levelLimit))) {
                                return;
                            }
                            var item = _.extend(_.pick(node.__field, 'label', 'type', 'name'), {
                                hasChildren: isEntity
                            });
                            autocompleteData.items[item.name] = item;
                        }, this);
                    }
                } else {
                    _.each(this.options.rootEntities, function(alias) {
                        autocompleteData.items[alias] = {
                            item: alias,
                            hasChildren: true
                        };
                    }, this);
                }
            } else {
                autocompleteData.items = this.operationsItems;
            }
        },

        /**
         * Set data source info into autocomplete data
         *
         * @param {Object} autocompleteData
         * @private
         */
        _setAutocompleteDataSource: function(autocompleteData) {
            var dataSourceKey = autocompleteData.itemCursorChild.replace(this.regex.cutDataSourceId, '');
            var dataSourceValue = '';

            if (dataSourceKey === autocompleteData.itemCursorChild) {
                dataSourceKey = '';
            } else {
                dataSourceValue = this.regex.cutDataSourceId.exec(autocompleteData.itemCursorChild);
                dataSourceValue = dataSourceValue ? dataSourceValue[1] : '';
            }

            autocompleteData.dataSourceKey = dataSourceKey;
            autocompleteData.dataSourceValue = dataSourceValue;
        }
    });

    return ExpressionEditorUtil;
});
