/*global define, require*/
/*jslint nomen: true*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/datetime-filter',
    'oro/filter/multiselect-filter',
    'oroentity/js/field-choice',
    'oroquerydesigner/js/field-condition'
], function($, _, __, DateTimeFilter, MultiSelectFilter) {
    'use strict';

    $.widget('oroactivity.activityCondition', {
        options: {
            listOption: {},
            filters: {},
            entitySelector: null,
            activityTpl: _.template($('#template-activity-condition-select').html())
        },

        _create: function () {
            var data = $.extend(true, {
                criterion: {
                    data: {
                        filterType: 'hasActivity',
                        dateRange: {},
                        activityType: {}
                    }
                }
            }, this.element.data('value'));

            var activityChoice = this.options.activityTpl({
                selected: data.criterion.data.filterType,
                hasActivityLabel: 'Has activity',
                hasNotActivityLabel: 'Has not activity'
            });
            this.$activityChoice = $(activityChoice);

            var listOption = JSON.parse(this.options.listOption);
            var typeChoices = {};
            _.each(listOption, function (options, id) {
                typeChoices[id] = options.label;
            });
            this.typeFilter = new MultiSelectFilter({
                label: __('oro.activitylist.widget.filter.activity.title'),
                choices: typeChoices
            });
            this.$typeChoice = $('<span class="active-filter"></span>')
                .html(this.typeFilter.render().$el);
            this.typeFilter.setValue(data.criterion.data.activityType);
            var filterOptions = _.findWhere(this.options.filters, {
                type: 'datetime'
            });
            if (!filterOptions) {
                throw new Error('Cannot find filter "datetime"');
            }
            this.rangeFilter = new (DateTimeFilter.extend(filterOptions))();
            this.rangeFilter.value = data.criterion.data.dateRange;
            this.$dateRangeChoice = $('<div><span class="active-filter"></span></div>');
            this.$dateRangeChoice.find('.active-filter').html(this.rangeFilter.render().$el);

            this.element.append(this.$activityChoice, this.$typeChoice, this.$dateRangeChoice);
            var $activitySelect = this.$activityChoice.find('select');
            $activitySelect.select2({
                minimumResultsForSearch: -1
            });

            this._on(this.element.children(), {
                change: this._onChange
            });
        },

        _onChange: function () {
            this.rangeFilter.applyValue();
            this.typeFilter.applyValue();
            var value = {
                columnName: $(this.options.entitySelector).val(),
                criterion: this._getFilterCriterion()
            };

            this.element.data('value', value);
            this.element.trigger('changed');
        },

        _getFilterCriterion: function () {
            return {
                filter: 'activityList',
                data: {
                    filterType: this.$activityChoice.find('select').val(),
                    dateRange: this.rangeFilter.getValue(),
                    activityType: this.typeFilter.getValue()
                }
            };
        }
    });
});
