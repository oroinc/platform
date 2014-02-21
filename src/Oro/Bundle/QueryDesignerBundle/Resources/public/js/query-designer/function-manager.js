/* global define */
define(['jquery', 'underscore', 'backbone', 'oroquerydesigner/js/query-designer/util'],
function($, _, Backbone, util) {
    'use strict';

    /**
     * View that represents all query designer functions
     *
     * @export  oroquerydesigner/js/query-designer/function-manager
     * @class   oro.queryDesigner.FunctionManager
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property */
        optionTemplate: _.template('<option value="<%- name %>" title="<%- title %>" data-group_name="<%- group_name %>" data-group_type="<%- group_type %>">' +
            '<%- label %>' +
        '</option>'),

        /**
         * @property {Array}
         */
        converters: null,

        /**
         * @property {Array}
         */
        aggregates: null,

        /**
         * @property {String}
         */
        activeFunctionGroupKey: null,

        /**
         * @property {Boolean}
         */
        isVisible: false,

        initialize: function()
        {
            this._getContainer().hide();

            var metadata = this.$el.closest('[data-metadata]').data('metadata');
            metadata = _.extend({converters: [], aggregates: []}, metadata);
            this.converters = metadata.converters;
            this.aggregates = metadata.aggregates;

            Backbone.View.prototype.initialize.apply(this, arguments);
        },

        /**
         * Sets functions conform the given criteria as active
         *
         * @param {Object}  criteria
         * @param {Boolean} convertersOnly
         */
        setActiveFunctions: function (criteria, convertersOnly) {
            if (_.isUndefined(convertersOnly)) {
                convertersOnly = false;
            }
            var foundGroups = [];
            _.each(this.converters, function(item, name) {
                if (util.isApplicable(item.applicable, criteria)) {
                    foundGroups.push({group_name: name, group_type: 'converters'});
                }
            });
            if (!convertersOnly) {
                _.each(this.aggregates, function(item, name) {
                    if (util.isApplicable(item.applicable, criteria)) {
                        foundGroups.push({group_name: name, group_type: 'aggregates'});
                    }
                });
            }

            var foundGroupKey = null;
            if (!_.isEmpty(foundGroups)) {
                foundGroupKey = '';
                _.each(foundGroups, function (group) {
                    foundGroupKey += (group.group_type + ':' + group.group_name + ';');
                });
            }
            if (!_.isNull(foundGroupKey) && foundGroupKey !== this.activeFunctionGroupKey) {
                util.clearSelect(this.$el);
                var content = '';
                var functions = [];
                _.each(foundGroups, function (foundGroup) {
                    _.each(this[foundGroup.group_type][foundGroup.group_name].functions, function (func) {
                        var existingFuncIndex = -1;
                        _.any(functions, function (val, index) {
                            if (val['name'] === func['name']) {
                                existingFuncIndex = index;
                                return true;
                            }
                            return false;
                        });
                        if (existingFuncIndex != -1) {
                            // override existing function and use its labels if needed
                            var existingLabel = functions[existingFuncIndex]['label'];
                            var existingTitle = functions[existingFuncIndex]['title'];
                            functions[existingFuncIndex] = _.extend({}, foundGroup, func);
                            if (_.isNull(functions[existingFuncIndex]['label'])) {
                                functions[existingFuncIndex]['label'] = existingLabel;
                            }
                            if (_.isNull(functions[existingFuncIndex]['title'])) {
                                functions[existingFuncIndex]['title'] = existingTitle;
                            }
                        } else {
                            functions.push(_.extend({}, foundGroup, func));
                        }
                    }, this);
                }, this);
                _.each(functions, function (func) {
                    content += this.optionTemplate(func);
                }, this);
                if (content != '') {
                    this.$el.append(content);
                }
                this.activeFunctionGroupKey = foundGroupKey;
            }

            if (this.isVisible) {
                if (_.isNull(foundGroupKey)) {
                    this._getContainer().hide();
                    this.isVisible = false;
                }
            } else {
                if (!_.isNull(foundGroupKey)) {
                    this._getContainer().show();
                    this.isVisible = true;
                }
            }
            if (this.isVisible) {
                this.$el.val('');
                this.$el.trigger('change')
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
            var item = this[groupType][groupName];
            if (!_.isUndefined(item)) {
                var func = _.findWhere(item.functions, {name: functionName});
                if (!_.isUndefined(func)) {
                    result = func.label;
                }
            }
            return result;
        },

        /**
         * Returns a control which contains a function selector
         *
         * @return {jQuery}
         */
        _getContainer: function () {
            return this.$el.closest('.control-group');
        }
    });
});
