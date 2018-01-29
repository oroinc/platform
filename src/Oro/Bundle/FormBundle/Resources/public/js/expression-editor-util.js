define(function(require) {
    'use strict';

    var ExpressionEditorUtil;
    var _ = require('underscore');

    ExpressionEditorUtil = function(options) {
        this._initialize(options);
    };

    ExpressionEditorUtil.prototype = {
        /**
         * RegEx, used for expression analyze, autocomplete and validate
         */
        regex: {
            itemPartBeforeCursor: /^.*[ ]/g, // 1 == prod|uct.id and ... => prod
            itemPartAfterCursor: /[ ].*$/g, // 1 == prod|uct.id and ... => uct.id
            findArray: /\[[^\[\]]*\]/, // [1, [2]] or [3] => [3]
            splitExpressionToItems: /([ \(\)])/, // item1 or (item2) => [item1, ,or, ,(,items2,)]
            clearStringSpecialSymbols: /\\\\|\\['"]/g, // \|\"|\' =>
            clearString: /"[^"]*"|'[^']*'/g, // 'string' => "string" => ""
            replaceDataSourceId: /^\[\s*\d+\s*\]/, // dataSource[1] => dataSource
            cutDataSourceId: /\[(.*?)\]/g, // dataSource[1] => "1", dataSource[] => ""
            removeDuplicatedWhitespaces: /\s+/g, // "  " => " "
            removeBracketsWhitespaces: /(\()\s|\s(\))/g, // "( " => "(", " )" => ")",
            nativeReplaceLogicalIOperations: /&&|\|\|/g, // && => &, || => &
            nativeReplaceAllowedBeforeTest: /true|false|indexOf/g, // true|false|... =>
            nativeFindNotAllowed: /[;a-z]/i // any not allowed symbols before JS execution
        },

        /**
         * Constants, used for expression build
         */
        strings: {
            childSeparator: '.',
            itemSeparator: ' ',
            arrayPlaceholder: '{array}'// used to remove nested arrays
        },

        /**
         * Initialize options
         */
        options: {
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
            entities: {
                root_entities: {},
                fields_data: {}
            },
            dataSource: {}
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

        /**
         * Generated list of all autocomplete items
         */
        allItems: null,

        /**
         * Generated list of operations autocomplete items
         */
        operationsItems: null,

        /**
         * Generated list of entities autocomplete items
         */
        entitiesItems: null,

        /**
         * Generated list of available data sources
         */
        dataSource: null,

        /**
         * Initialize util
         *
         * @param {Object} options
         */
        _initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options.component = this;

            this.dataSource = _.keys(this.options.dataSource);

            this._prepareItems();
        },

        /**
         * Validate expression syntax
         *
         * @param {String} expression
         * @return {Boolean}
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
         * @param {String} expression
         * @return {Boolean|String}
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

            var items = expression.split(this.regex.splitExpressionToItems);
            _.each(items, this._convertItemToNativeJS, this);

            return items.join('');
        },

        /**
         * Convert item, or group of items, to native JS code
         *
         * @param {String} item
         * @param {Integer} i
         * @param {Array} items
         * @private
         */
        _convertItemToNativeJS: function(item, i, items) {
            if (item.length === 0) {
                return item;
            }
            var prevSeparator = i - 1;
            var prevItem = prevSeparator - 1;
            var nextSeparator = i + 1;
            var nextItem = nextSeparator + 1;

            var groupedItem = item + this.strings.itemSeparator + items[nextItem];
            if (items[nextItem] && this.allItems[groupedItem]) {
                // items with whitespaces, for example: `not in`
                item = groupedItem;
                items[nextItem] = '';
                items[nextSeparator] = '';

                nextSeparator = nextItem + 1;
                nextItem = nextSeparator + 1;
            }

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
         * @param {String} expression
         * @return {Boolean|String}
         * @private
         */
        _clearStrings: function(expression) {
            // remove `\`, `\"` and `\'` symbols
            expression = expression.replace(this.regex.clearStringSpecialSymbols, '');
            // clear strings and convert quotes
            expression = expression.replace(this.regex.clearString, '""');
            return expression;
        },

        /**
         * Validate data source and replace data source [id]
         *
         * @param {String} expression
         * @return {Boolean|String}
         * @private
         */
        _clearDataSource: function(expression) {
            var dataSources = this.dataSource;
            if (!dataSources.length) {
                return expression;
            }

            var items = expression.split(this.regex.splitExpressionToItems);
            var item;
            // check all items
            for (var i = 0; i < items.length; i++) {
                item = items[i];
                // check all data sources in item
                for (var j = 0; j < dataSources.length; j++) {
                    item = item.split(new RegExp('(' + dataSources[j] + ')'));
                    if (item.length < 2 || item[0] !== '') {
                        // data source not found in item or not in item beginning
                        continue;
                    } else if (item[2].indexOf(this.strings.childSeparator) === 0) {
                        // not valid
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
         * @param {String} expression
         * @return {String|Boolean}
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
                    // we have not closed array
                    return false;
                }

                array = array[0];
                if (!this._validateNativeJS(array.replace(new RegExp(arrayPlaceholder, 'g'), '[]'))) {
                    // array not valid
                    return false;
                }

                changedExpression = expression.replace(array, arrayPlaceholder);
                if (changedExpression === expression) {
                    return false;// recursion
                }

                expression = changedExpression;
            }

            expression = expression.replace(new RegExp(arrayPlaceholder, 'g'), '[]');

            return expression;
        },

        /**
         * Remove duplicated/extra whitespaces
         *
         * @param {String} expression
         * @return {String|Boolean}
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
         * @param {String} item
         * @return {String}
         * @private
         */
        _getNativeJS: function(item) {
            var foundItem = this.allItems[item];
            if (this.itemToNativeJS[item] !== undefined) {
                return this.itemToNativeJS[item];
            } else if (foundItem && this.itemToNativeJS[foundItem.type] !== undefined) {
                return this.itemToNativeJS[foundItem.type];
            }
            return item;
        },

        /**
         * Validate native JS expression
         *
         * @param {String} expression
         * @return {Boolean}
         * @private
         */
        _validateNativeJS: function(expression) {
            var testExpression = expression.replace(this.regex.nativeReplaceAllowedBeforeTest, '');
            if (this.regex.nativeFindNotAllowed.test(testExpression)) {
                return false;
            }
            // replace all "&&" and "||" to "&", because if first part of "&&" or "||" return true or false - JS ignore(do not execute) second part
            expression = expression.replace(this.regex.nativeReplaceLogicalIOperations, '&');
            try {
                var f = new Function('return ' + expression);
                var result = f();
                return _.isBoolean(result) || !_.isUndefined(result);
            } catch (e) {
                return false;
            }
        },

        /**
         * Build autocomplete data by expression and cursor position
         *
         * @param {String} expression
         * @param {Integer} position
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
         * @param {String} item
         */
        updateAutocompleteItem: function(autocompleteData, item) {
            var positionModifier = 0;
            var hasChild = !!autocompleteData.items[item].child;
            var hasDataSource = this.dataSource.indexOf(item) !== -1;

            if (hasDataSource) {
                item += '[]';
                positionModifier = hasChild ? -2 : -1;
            }

            item += hasChild ? this.strings.childSeparator : this.strings.itemSeparator;

            autocompleteData.itemChild[autocompleteData.itemLastChildIndex] = item;

            this._updateAutocompleteExpression(autocompleteData);

            autocompleteData.position = autocompleteData.expression.length - autocompleteData.afterItem.length +
                positionModifier;
        },

        /**
         * Set new data source value into autocomplete data
         *
         * @param {Object} autocompleteData
         * @param {String} dataSourceValue
         */
        updateDataSourceValue: function(autocompleteData, dataSourceValue) {
            autocompleteData.itemChild[autocompleteData.itemCursorIndex] = autocompleteData
                .itemCursorChild.replace(this.regex.cutDataSourceId, '[' + dataSourceValue + ']');

            this._updateAutocompleteExpression(autocompleteData);

            autocompleteData.position = autocompleteData.expression.length - autocompleteData.afterItem.length;
        },

        /**
         * Generate list of all autocomplete items from initialize options
         *
         * @private
         */
        _prepareItems: function() {
            this.allItems = {};
            this.operationsItems = {};
            this.entitiesItems = {};

            this._prepareEntitiesItems();
            this._prepareOperationsItems();
        },

        /**
         * Add new autocomplete item
         *
         * @param {String} group
         * @param {Object} items
         * @param {String} item
         * @param {Object} itemInfo
         * @private
         */
        _addItem: function(group, items, item, itemInfo) {
            if (itemInfo.child !== undefined && _.isEmpty(itemInfo.child)) {
                // item is collection without child
                return;
            }

            itemInfo.group = group;
            if (itemInfo.parentItem) {
                itemInfo.item = itemInfo.parentItem + this.strings.childSeparator + item;
            } else {
                itemInfo.item = item;
            }

            this.allItems[itemInfo.item] = itemInfo;
            items[item] = itemInfo;
        },

        /**
         * Generate list of operations autocomplete items from initialize options
         *
         * @private
         */
        _prepareOperationsItems: function() {
            _.each(this.options.allowedOperations, function(type) {
                _.each(this.options.operations[type], function(item) {
                    this._addItem('operations', this.operationsItems, item, {
                        type: type
                    });
                }, this);
            }, this);
        },

        /**
         * Generate list of entities autocomplete items from initialize options
         *
         * @private
         */
        _prepareEntitiesItems: function() {
            _.each(this.options.entities.root_entities, function(item, entity) {
                this._addItem('entities', this.entitiesItems, item, {
                    child: this._getEntityChild(1, item, entity)
                });
            }, this);
        },

        /**
         * Prepare entity child for autocomplete items
         *
         * @param {Integer} level
         * @param {String} parentItem
         * @param {String} entity
         * @return {Object}
         * @private
         */
        _getEntityChild: function(level, parentItem, entity) {
            var child = {};

            level++;
            if (level > this.options.itemLevelLimit) {
                return child;
            }

            _.each(this.options.entities.fields_data[entity], function(itemInfo, item) {
                var childItem = parentItem + this.strings.childSeparator + item;
                itemInfo.parentItem = parentItem;
                if (itemInfo.type === 'relation') {
                    itemInfo.child = this._getEntityChild(level, childItem, itemInfo.relation_alias);
                }
                this._addItem('entities', child, item, itemInfo);
            }, this);

            return child;
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
                group: '', // group of items for autocomplete
                items: {}, // list of items for autocomplete
                dataSourceKey: '', // key of data source if item is data source
                dataSourceValue: ''// value of data source if item is data source
            };

            this._setAutocompleteItem(autocompleteData);
            this._setAutocompleteGroup(autocompleteData);
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
        _setAutocompleteGroup: function(autocompleteData) {
            var prevItemStr = _.trim(autocompleteData.beforeItem).split(this.strings.itemSeparator).pop();
            var prevItem = this.allItems[prevItemStr] || {};

            if (!prevItemStr || prevItemStr === '(' || prevItem.group === 'operations') {
                autocompleteData.group = 'entities';
            } else {
                autocompleteData.group = 'operations';
            }
        },

        /**
         * Set autocomplete items into autocomplete data
         *
         * @param {Object} autocompleteData
         * @private
         */
        _setAutocompleteItems: function(autocompleteData) {
            var items = this[autocompleteData.group + 'Items'];
            var item;
            for (var i = 0; i < autocompleteData.itemChild.length - 1; i++) {
                item = autocompleteData.itemChild[i];
                item = item.replace(this.regex.cutDataSourceId, '');
                items = items[item] || {};
                items = items.child || null;
                if (!items) {
                    break;
                }
            }

            autocompleteData.items = items || {};
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
    };

    return ExpressionEditorUtil;
});
