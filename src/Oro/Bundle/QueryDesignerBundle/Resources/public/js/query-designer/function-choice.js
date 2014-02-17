/*global define*/
define(['jquery', 'underscore', 'oro/query-designer/util'], function($, _, util) {
    'use strict';

    /**
     * Widget that represents all query designer functions
     *
     * @export  oro/query-designer/function-choice
     */
    $.widget('oroquerydesigner.functionChoice', {
        options: {
            optionTemplate: _.template('<option value="<%- name %>" title="<%- title %>" data-group_name="<%- group_name %>" data-group_type="<%- group_type %>">' +
                '<%- label %>' +
            '</option>'),
            converters: [],
            aggregates: []
        },

        activeFunctionGroupKey: null,
        isVisible: false,

        _create: function () {
            this.hide();
        },

        /**
         * Returns a control which contains a function selector
         *
         * @return {jQuery}
         */
        _getContainer: function () {
            return this.element.closest('.control-group');
        },

        /**
         * Shows widget's container
         */
        show: function () {
            this._getContainer().show();
            this.isVisible = true;
        },

        /**
         * Hides widget's container
         */
        hide: function () {
            this._getContainer().hide();
            this.isVisible = false;
        },

        /**
         * Sets functions conform the given criteria as active
         *
         * @param {Object}  criteria
         * @param {Boolean} convertersOnly
         */
        setActiveFunctions: function (criteria, convertersOnly) {
            var options = this.options;
            var foundGroups = [];
            var foundGroupKey = null;
            var content = '';
            var functions = [];

            _.each(options.converters, function (item, name) {
                if (util.isApplicable(item.applicable, criteria)) {
                    foundGroups.push({ group_name: name, group_type: 'converters' });
                }
            });

            if (!convertersOnly) {
                _.each(options.aggregates, function (item, name) {
                    if (util.isApplicable(item.applicable, criteria)) {
                        foundGroups.push({ group_name: name, group_type: 'aggregates' });
                    }
                });
            }

            if (!_.isEmpty(foundGroups)) {
                foundGroupKey = '';
                _.each(foundGroups, function (group) {
                    foundGroupKey += group.group_type + ':' + group.group_name + ';';
                });
            }

            if (foundGroupKey && (foundGroupKey !== this.activeFunctionGroupKey)) {
                util.clearSelect(this.element);

                _.each(foundGroups, function (foundGroup) {
                    _.each(options[foundGroup.group_type][foundGroup.group_name].functions, function (func) {
                        var existingFuncIndex = -1;

                        _.any(functions, function (val, index) {
                            if (val.name === func.name) {
                                existingFuncIndex = index;
                                return true;
                            }
                            return false;
                        });

                        if (existingFuncIndex !== -1) {
                            // override existing function and use its labels if needed
                            var existingLabel = functions[existingFuncIndex].label;
                            var existingTitle = functions[existingFuncIndex].title;
                            functions[existingFuncIndex] = _.extend({}, foundGroup, func);
                            if (_.isNull(functions[existingFuncIndex].label)) {
                                functions[existingFuncIndex].label = existingLabel;
                            }
                            if (_.isNull(functions[existingFuncIndex].title)) {
                                functions[existingFuncIndex].title = existingTitle;
                            }
                        } else {
                            functions.push(_.extend({}, foundGroup, func));
                        }
                    });
                });

                _.each(functions, function (func) {
                    content += options.optionTemplate(func);
                });

                if (content !== '') {
                    this.element.append(content);
                }

                this.activeFunctionGroupKey = foundGroupKey;
            }

            if (this.isVisible) {
                if (!foundGroupKey) {
                    this.hide();
                }
            } else {
                if (foundGroupKey) {
                    this.show();
                }
            }

            if (this.isVisible) {
                this.element.val('');
                this.element.trigger('change');
            }
        },

        /**
         * Returns translated string represents a name of the given function
         *
         * @param {String} groupType
         * @param {String} groupName
         * @param {String} functionName
         * @return {String}
         */
        getFunctionLabel: function (groupType, groupName, functionName) {
            var result = functionName;
            var item = this.options[groupType][groupName];
            if (item) {
                var func = _.findWhere(item.functions, { name: functionName });
                if (func) {
                    result = func.label;
                }
            }
            return result;
        }
    });

    return $;
});
