/*jshint -W054 *///ignore The Function constructor is a form of eval
define(function(require) {
    'use strict';

    var ExpressionEditorUtil;
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');

    ExpressionEditorUtil = BaseClass.extend({
        /**
         * RegEx, used for expression analyze, autocomplete and validate
         */
        regex: {
            itemPartBeforeCursor: /^.*[ ]/g,   //1 == prod|uct.id and ... => prod
            itemPartAfterCursor: /[ ].*$/g,    //1 == prod|uct.id and ... => uct.id
            findArray: /\[[^\[\]]*\]/,  //[1, [2]] or [3] => [3]
            splitExpressionToItems: /([ \(\)])/,    //item1 or (item2) => [item1, ,or, ,(,items2,)]
            clearStringSpecialSymbols: /\\\\|\\['"]/g,  //\|\"|\' =>
            clearString: /"[^"]*"|'[^']*'/g,    //'string' => "string" => ""
            replaceDataSourceId: /^\[\s*\d+\s*\]/,  //dataSource[1] => dataSource
            cutDataSourceId: /\[(.*?)\]/g,   //dataSource[1] => "1", dataSource[] => ""
            removeDuplicatedWhitespaces: /\s+/g,    //"  " => " "
            removeBracketsWhitespaces: /(\()\s|\s(\))/g,   //"( " => "(", " )" => ")",
            nativeReplaceLogicalIOperations: /&&|\|\|/g,   //&& => &, || => &
            nativeReplaceAllowedBeforeTest: /true|false|indexOf/g,   //true|false|... =>
            nativeFindNotAllowed: /[;a-z]/i     //any not allowed symbols before JS execution
        },

        /**
         * Constants, used for expression build
         */
        strings: {
            childSeparator: '.',
            itemSeparator: ' ',
            arrayPlaceholder: '{array}'//used to remove nested arrays
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
         * Rules to convert single item to native JS
         */
        itemToNativeJS: {
            'and': '&&',
            'or': '||',
            'boolean': 'true',
            'relation': 'true',
            'datetime': '0',
            'integer': '0',
            'float': '0',
            'standalone': '""',
            'string': '""',
            'in': '$next.indexOf($prev) != -1',
            'not in': '$next.indexOf($prev) == -1',
            'matches': '$prev.indexOf($next) != -1',
            'enum': '[]',
            'collection': '[]'
        },

        fieldTypeMap: {
            'text': 'string',
            'money': 'float',
            'decimal': 'float',
            'manyToMany': 'relation',
            'oneToMany': 'relation',
            'manyToOne': 'relation',
            'ref-many': 'relation',
            'ref-one': 'relation'
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
         * Instance of EntityStructureDataProvider that contains data about entity fields
         */
        entityDataProvider: null,

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
            this._prepareOperationsItems();
            ExpressionEditorUtil.__super__.initialize.call(this, options);
        },

        /**
         * Validate expression syntax
         *
         * @param {string} expression
         * @return {boolean}
         */
        validate: function(expression) {
            expression = _.trim(expression);
            if (expression.length === 0) {
                return true;
            }

            expression = this._convertExpressionToNativeJS(expression);
            if (expression === false) {
                return false;
            }
            return this._validateNativeJS(expression);
        },

        /**
         * Convert expression to native JS code
         *
         * @param {string} expression
         * @return {boolean|string}
         * @private
         */
        _convertExpressionToNativeJS: function(expression) {
            var clearMethods = ['_clearStrings', '_clearDataSource', '_clearArrays', '_clearSeparators'];
            for (var i = 0; i < clearMethods.length; i++) {
                expression = this[clearMethods[i]](expression);
                if (expression === false) {
                    return false;
                }
            }

            var items = this._splitExpressionToItems(expression);
            _.each(items, this._convertItemToNativeJS, this);

            return items.indexOf(void 0) === -1 ? items.join('') : false;
        },

        /**
         * Splits expression to array of strings taking in account that operations can contain space symbols
         *
         * @param {string} expression
         * @return {Array.<string>}
         * @private
         */
        _splitExpressionToItems: function(expression) {
            var items = _.compact(expression.split(this.regex.splitExpressionToItems));
            _.each(this.operationsItems, function(operation) {
                var operationParts = operation.item.split(this.regex.splitExpressionToItems);
                if (operationParts.length > 1) {
                    for (var i = items.length - operationParts.length; i >= 0; i--) {
                        var itemsPart = _.clone(items).splice(i, operationParts.length);
                        if (itemsPart.join('') === operationParts.join('')) {
                            items.splice(i, operationParts.length, operation.item);
                        }
                    }
                }
            }, this);
            return items;
        },

        /**
         * Convert item, or group of items, to native JS code
         *
         * @param {string} item
         * @param {integer} i
         * @param {Array} items
         * @private
         */
        _convertItemToNativeJS: function(item, i, items) {
            if (_.trim(item).length === 0) {
                return item;
            }
            var prevSeparator = i - 1;
            var prevItem = prevSeparator - 1;
            var nextSeparator = i + 1;
            var nextItem = nextSeparator + 1;

            var nativeJS = this._getNativeJS(item);
            if (nativeJS === item) {
                items[i] = nativeJS;
                return nativeJS;
            }

            if (nativeJS.indexOf('$prev') !== -1 && items[prevItem]) {
                nativeJS = nativeJS.replace('$prev', items[prevItem]);
                items[prevItem] = '';
                items[prevSeparator] = '';
            }

            if (nativeJS.indexOf('$next') !== -1 && items[nextItem]) {
                nativeJS = nativeJS.replace('$next', this._convertItemToNativeJS(items[nextItem], nextItem, items));
                items[nextItem] = '';
                items[nextSeparator] = '';
            }

            items[i] = nativeJS;
            return nativeJS;
        },

        /**
         * Make all strings empty, convert all strings to use double quote
         *
         * @param {string} expression
         * @return {boolean|string}
         * @private
         */
        _clearStrings: function(expression) {
            //remove `\`, `\"` and `\'` symbols
            expression = expression.replace(this.regex.clearStringSpecialSymbols, '');
            //clear strings and convert quotes
            expression = expression.replace(this.regex.clearString, '""');
            return expression;
        },

        /**
         * Validate data source and replace data source [id]
         *
         * @param {string} expression
         * @return {boolean|string}
         * @private
         */
        _clearDataSource: function(expression) {
            if (!this.dataSourceNames.length) {
                return expression;
            }

            var items = this._splitExpressionToItems(expression);
            var item;
            //check all items
            for (var i = 0; i < items.length; i++) {
                item = items[i];
                //check all data sources in item
                for (var j = 0; j < this.dataSourceNames.length; j++) {
                    item = item.split(new RegExp('(' + this.dataSourceNames[j] + ')'));
                    if (item.length < 2 || item[0] !== '') {
                        //data source not found in item or not in item beginning
                        continue;
                    } else if (item[2].indexOf(this.strings.childSeparator) === 0) {
                        //not valid
                        return false;
                    }

                    item[2] = item[2].replace(this.regex.replaceDataSourceId, '');

                    items[i] = item.join('');
                    break;
                }
            }

            return items.join('');
        },

        /**
         * Validate arrays and make them empty, remove nested arrays
         *
         * @param {string} expression
         * @return {string|boolean}
         * @private
         */
        _clearArrays: function(expression) {
            var array;
            var changedExpression;
            var arrayPlaceholder = this.strings.arrayPlaceholder;

            /*
            while we have an [ or ]
                [1, [2]] or [3]     => [1, {array}] or [3]
                [1, {array}] or [3] => {array} or [3]
                {array} or [3]      => {array} or {array}
            then
                {array} or {array}  => [] or []
             */
            while (expression.indexOf('[') !== -1 && expression.indexOf(']') !== -1) {
                array = expression.match(this.regex.findArray);
                if (array.length === 0) {
                    //we have not closed array
                    return false;
                }

                array = array[0];
                if (!this._validateNativeJS(array.replace(new RegExp(arrayPlaceholder, 'g'), '[]'))) {
                    //array not valid
                    return false;
                }

                changedExpression = expression.replace(array, arrayPlaceholder);
                if (changedExpression === expression) {
                    return false;//recursion
                }

                expression = changedExpression;
            }

            expression = expression.replace(new RegExp(arrayPlaceholder, 'g'), '[]');

            return expression;
        },

        /**
         * Remove duplicated/extra whitespaces
         *
         * @param {string} expression
         * @return {string|boolean}
         * @private
         */
        _clearSeparators: function(expression) {
            expression = expression.replace(this.regex.removeDuplicatedWhitespaces, ' ');
            expression = expression.replace(this.regex.removeBracketsWhitespaces, '$1$2');
            return expression;
        },

        /**
         * Try to find native JS code for expression item
         *
         * @param {string} item
         * @return {string}
         * @private
         */
        _getNativeJS: function(item) {
            var result;
            if (item in this.itemToNativeJS) {
                result = this.itemToNativeJS[item];
            } else if (item in this.operationsItems) {
                var operation = this.operationsItems[item];
                if (operation.type in this.itemToNativeJS) {
                    result = this.itemToNativeJS[operation.type];
                } else {
                    result = item;
                }
            }
            if (!result) {
                var entity = _.first(item.split(this.strings.childSeparator));
                if (this.options.rootEntities.indexOf(entity) !== -1) {
                    var entityTreeNode = this.entityDataProvider.getEntityTreeNodeByPropertyPath(item);
                    if (entityTreeNode && entityTreeNode.__isField) {
                        var fieldType = entityTreeNode.__field.type;
                        if (fieldType in this.fieldTypeMap) {
                            fieldType = this.fieldTypeMap[fieldType];
                        }
                        if (fieldType in this.itemToNativeJS) {
                            result = this.itemToNativeJS[fieldType];
                        }
                    }
                }
            }
            return result || item;
        },

        /**
         * Validate native JS expression
         *
         * @param {string} expression
         * @return {boolean}
         * @private
         */
        _validateNativeJS: function(expression) {
            var testExpression = expression.replace(this.regex.nativeReplaceAllowedBeforeTest, '');
            if (this.regex.nativeFindNotAllowed.test(testExpression)) {
                return false;
            }
            //replace all "&&" and "||" to "&", because if first part of "&&" or "||" return true or false - JS ignore(do not execute) second part
            expression = expression.replace(this.regex.nativeReplaceLogicalIOperations, '&');
            try {
                var f = new Function('return ' + expression);
                var result = f();
                return !_.isUndefined(result);
            } catch (e) {
                return false;
            }
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
                expression: expression,//full expression
                position: position,//cursor position
                item: '',//item under cursor or just "item"
                beforeItem: '',//part of expression before item
                afterItem: '',//part of expression after item
                itemChild: [],//child of item
                itemLastChildIndex: 0,//index of last child of item
                itemLastChild: '',//last child of item
                itemCursorIndex: 0,//index of an item child under cursor
                itemCursorChild: '',//item child under cursor
                itemsType: '',// `entities` or `operations`
                items: {},//list of items for autocomplete
                dataSourceKey: '',//key of data source if item is data source
                dataSourceValue: ''//value of data source if item is data source
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
