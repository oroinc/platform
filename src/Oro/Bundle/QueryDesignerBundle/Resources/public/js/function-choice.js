define(['jquery', 'underscore', 'jquery-ui'], function($, _) {
    'use strict';

    /**
     * Widget that represents all query designer functions
     */
    $.widget('oroquerydesigner.functionChoice', {
        options: {
            fieldChoiceSelector: '',
            optionTemplate: _.template('<option value="<%- data.name %>" title="<%- data.title %>" ' +
                    'data-group_name="<%- data.group_name %>" data-group_type="<%- data.group_type %>" ' +
                    'data-return_type="<%- data.return_type %>">' +
                    '<%- data.label %>' +
                '</option>'),
            converters: [],
            aggregates: []
        },

        activeFunctionGroupKey: null,

        _create: function() {
            this._disable(true);
            this._bindFieldChoice();
        },

        /**
         * Sets functions conform the given criteria as active
         *
         * @param {Object}  criteria
         * @param {Boolean} convertersOnly
         */
        setActiveFunctions: function(criteria, convertersOnly) {
            var self = this;
            var options = this.options;
            var foundGroups = [];
            var foundGroupKey = null;
            var content = '';
            var functions = [];

            _.each(options.converters, function(item, name) {
                if (self._matchApplicable(item.applicable, criteria)) {
                    foundGroups.push({group_name: name, group_type: 'converters'});
                }
            });

            if (!convertersOnly) {
                _.each(options.aggregates, function(item, name) {
                    if (self._matchApplicable(item.applicable, criteria)) {
                        foundGroups.push({group_name: name, group_type: 'aggregates'});
                    }
                });
            }

            if (!_.isEmpty(foundGroups)) {
                foundGroupKey = '';
                _.each(foundGroups, function(group) {
                    foundGroupKey += group.group_type + ':' + group.group_name + ';';
                });
            }

            if (foundGroupKey && (foundGroupKey !== this.activeFunctionGroupKey)) {
                this._clearSelect();

                _.each(foundGroups, function(foundGroup) {
                    _.each(options[foundGroup.group_type][foundGroup.group_name].functions, function(func) {
                        var existingFuncIndex = -1;

                        _.any(functions, function(val, index) {
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

                _.each(functions, function(func) {
                    content += options.optionTemplate({data: func});
                });

                if (content !== '') {
                    this.element.append(content);
                }

                this.activeFunctionGroupKey = foundGroupKey;
            }

            this._disable(!foundGroupKey);

            this.element.val('').trigger('change');
        },

        _matchApplicable: function(applicable, criteria) {
            return _.find(applicable, function(item) {
                return _.every(item, function(value, key) {
                    return criteria[key] === value;
                });
            });
        },

        _clearSelect: function() {
            this.element.find('option').not('[value=""]').remove();
        },

        _disable: function(flag) {
            var $elem = this.element;
            if ($elem.data('select2')) {
                $elem.select2('enable', !flag);
            } else {
                $elem.attr('disabled', flag);
            }
            var $widgetContainer = $elem.inputWidget('container');
            if ($widgetContainer) {
                $widgetContainer.toggleClass('disabled', flag);
            }
        },

        _bindFieldChoice: function() {
            var $fields;
            var self = this;
            if (this.options.fieldChoiceSelector) {
                $fields = $(this.options.fieldChoiceSelector);
                $fields.change(function(e) {
                    var criteria = $fields.fieldChoice('getApplicableConditions', $(e.target).val());
                    self.setActiveFunctions(criteria);
                });
            }
        }
    });

    return $;
});
