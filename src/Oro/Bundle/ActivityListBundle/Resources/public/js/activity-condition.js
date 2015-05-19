/*global define, require*/
/*jslint nomen: true*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/datetime-filter',
    'oro/filter/choice-filter',
    'oro/filter/multiselect-filter',
    'oroentity/js/field-choice',
    'oroquerydesigner/js/field-condition'
], function($, _, __, DateTimeFilter, ChoiceFilter, MultiSelectFilter) {
    'use strict';

    $.widget('oroactivity.activityCondition', {
        options: {
            listOption: {},
            filters: {},
            entitySelector: null,
            filterContainer: '<span class="active-filter">'
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

            this.activityFilter = new ChoiceFilter({
                caret: '',
                templateSelector: '#simple-choice-filter-template-embedded',
                choices: {
                    hasActivity: __('oro.activityCondition.hasActivity'),
                    hasNotActivity: __('oro.activityCondition.hasNotActivity')
                }
            });
            this.activityFilter.setValue({
                type: data.criterion.data.filterType
            });
            this.$activityChoice = $(this.options.filterContainer).html(this.activityFilter.render().$el);

            var listOption = JSON.parse(this.options.listOption);
            var typeChoices = {};
            _.each(listOption, function (options, id) {
                typeChoices[id] = options.label;
            });
            this.typeFilter = new MultiSelectFilter({
                label: __('oro.activityCondition.listOfActivityTypes'),
                choices: typeChoices
            });
            this.$typeChoice = $(this.options.filterContainer).html(this.typeFilter.render().$el);
            this.typeFilter.setValue(data.criterion.data.activityType);
            var filterOptions = _.findWhere(this.options.filters, {
                type: 'datetime'
            });
            if (!filterOptions) {
                throw new Error('Cannot find filter "datetime"');
            }
            this.rangeFilter = new (DateTimeFilter.extend(filterOptions))();
            this.rangeFilter.value = data.criterion.data.dateRange;
            this.$dateRangeChoice = $(this.options.filterContainer).html(this.rangeFilter.render().$el);

            this.element.append(this.$activityChoice, '-', this.$typeChoice, '-', this.$dateRangeChoice);

            this._on(this.element.children(), {
                change: this._onChange
            });
        },

        _onChange: function () {
            this.rangeFilter.applyValue();
            this.typeFilter.applyValue();
            this.activityFilter.applyValue();

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
                    filterType: this.$activityChoice.find(':input').val(),
                    dateRange: this.rangeFilter.getValue(),
                    activityType: this.typeFilter.getValue()
                }
            };
        }
    });
});
