define([
    'jquery',
    'underscore',
    'oroquerydesigner/js/field-condition'
], function($, _) {
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
                if (model.get('name') === this._getColumnName()) {
                    this.element.closest('.condition').find('.close').click();
                }
            }, this);
            if (data && data.columnName) {
                this.selectField(data.columnName);
                this._renderFilter(data.columnName);
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
            var self = this;
            var fieldChoice = this.$fieldChoice.fieldChoice().data('oroentity-fieldChoice');
            var originalSelect2Data = fieldChoice._select2Data;

            fieldChoice._select2Data = function(path) {
                originalSelect2Data.apply(this, arguments);

                return self._getAggregatedSelectData();
            };
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
            var columnFunc = this._getColumnFunc(columnName);

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
            return this.element.find('input.select').select2('val');
        },

        _getColumnFunc: function(columnName) {
            var column = this.options.columnsCollection.findWhere({name: columnName});
            if (_.isEmpty(column)) {
                return;
            }

            return column.get('func');
        },

        _getFilterCriterion: function() {
            var criterion = this._superApply(arguments);
            $.extend(true, criterion, {'data': {'params': {'filter_by_having': true}}});

            return criterion;
        }
    });
});
