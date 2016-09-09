define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroquerydesigner/js/field-condition'
], function($, _, __) {
    'use strict';

    $.widget('oroauditquerydesigner.aggregatedFieldCondition', $.oroquerydesigner.fieldCondition, {
        options: {
            columnsCollection: null
        },

        _create: function() {
            var data = this.element.data('value');

            this.$fieldChoice = $('<input>').addClass(this.options.fieldChoiceClass);
            this.$filterContainer = $('<span>').addClass(this.options.filterContainerClass);
            this.element.append(this.$fieldChoice, this.$filterContainer);

            this.$fieldChoice.fieldChoice(this.options.fieldChoice);

            this._updateFieldChoice();
            this.options.columnsCollection.on('remove', function(model) {
                if (model.get('label') === this._getColumnLabel()) {
                    this.element.closest('.condition').find('.close').click();
                }
            }, this);
            this.options.columnsCollection.on('change:func change:label', function(model) {
                if (model._previousAttributes.label === this._getColumnLabel()) {
                    this.element.closest('.condition').find('.close').click();
                }
            }, this);
            if (data && data.columnName && data.func) {
                var column = this._getColumnByNameAndFunc(data.columnName, data.func);
                if (column) {
                    this.$fieldChoice.fieldChoice('setData', {id: column.get('name'), text: column.get('label')});
                    this._renderFilter(column.get('name'));
                }
            }

            this._on(this.$fieldChoice, {
                changed: function(e, fieldId) {
                    $(':focus').blur();
                    // reset current value on field change
                    this.element.data('value', {});
                    this._renderFilter(fieldId);
                    e.stopPropagation();
                }
            });

            this._on(this.$filterContainer, {
                change: function() {
                    if (this.filter) {
                        this.filter.applyValue();
                    }
                }
            });
        },

        _updateFieldChoice: function() {
            var fieldChoice = this.$fieldChoice.fieldChoice().data('oroentity-fieldChoice');
            fieldChoice._select2Data = _.bind(this._getAggregatedSelectData, this);

            fieldChoice.setData = function(data) {
                this.element.inputWidget('data', data, true);
            };

            var self = this;

            fieldChoice.formatChoice = _.wrap(fieldChoice.formatChoice, function(original) {
                var formatted = original.apply(this, _.rest(arguments));
                var func = self._getCurrentFunc();
                if (func && func.name) {
                    formatted += ' (' + func.name + ')';
                }

                return formatted;
            });

            fieldChoice.getApplicableConditions = _.wrap(
                fieldChoice.getApplicableConditions,
                function(original) {
                    var conditions = original.apply(this, _.rest(arguments));
                    var func = self._getCurrentFunc();
                    if (func && func.return_type) {
                        conditions.type = func.return_type;
                    }

                    return conditions;
                }
            );
        },

        _getAggregatedSelectData: function() {
            return _.map(
                this._getAggregatedColumns(),
                function(model) {
                    return {
                        id: model.get('name'),
                        text: model.get('label')
                    };
                }
            );
        },

        _getAggregatedColumns: function() {
            return _.filter(
                this.options.columnsCollection.models,
                _.compose(_.negate(_.isEmpty), _.property('func'), _.property('attributes'))
            );
        },

        _onUpdate: function() {
            var value;
            var columnName = this._getColumnName();
            var columnFunc = this._getCurrentFunc();

            if (this.filter && !this.filter.isEmptyValue() && !_.isEmpty(columnFunc)) {
                value = {
                    columnName: columnName,
                    criterion: this._getFilterCriterion(),
                    func: columnFunc
                };
            } else {
                value = {};
            }

            this.element.data('value', value);
            this.element.trigger('changed');
        },

        _getColumnName: function() {
            return this.element.find('input.select').inputWidget('val');
        },

        _getColumnLabel: function() {
            var obj = this.element.find('input.select').inputWidget('data');

            return obj ? obj.text : undefined;
        },

        _getCurrentFunc: function() {
            var column = this.options.columnsCollection.findWhere({label: this._getColumnLabel()});
            if (_.isEmpty(column)) {
                return;
            }

            return column.get('func');
        },

        _getColumnByNameAndFunc: function(name, func) {
            if (!func) {
                return;
            }

            return _.find(this.options.columnsCollection.where({name: name}), function(column) {
                return column.get('func') && column.get('func').name === func.name;
            });
        },

        _getFilterCriterion: function() {
            var criterion = this._superApply(arguments);
            $.extend(true, criterion, {'data': {'params': {'filter_by_having': true}}});

            return criterion;
        }
    });
});
