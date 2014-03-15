/*global define, require*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'orofilter/js/map-filter-module-name',
    './segment-choice', 'jquery-ui'
], function ($, _, __, mapFilterModuleName) {
    'use strict';

    /**
     * Apply segment widget
     */
    $.widget('orosegment.segmentCondition', {
        options: {
            segmentChoice: {},
            segmentChoiceClass: 'select',
            filters: [],
            filterContainerClass: 'active-filter'
        },

        _create: function () {
            var data = this.element.data('value');

            this.$segmentChoice = $('<input>').addClass(this.options.segmentChoiceClass);
            this.$filterContainer = $('<span>').addClass(this.options.filterContainerClass);
            this.element.append(this.$segmentChoice, this.$filterContainer);

            this.$segmentChoice.segmentChoice(this.options.segmentChoice);

            if (data && data.columnName) {
                this.selectSegment(data.columnName);
                this._renderSegment(data.columnName);
            }

            this.$segmentChoice.on('changed', _.bind(function (e, fieldId) {
                $(':focus').blur();
                // reset current value on segment change
                this.element.data('value', {});
                this._renderSegment(fieldId);
                e.stopPropagation();
            }, this));

            this.$filterContainer.on('change', _.bind(function () {
                if (this.filter) {
                    this.filter.applyValue();
                }
            }, this));
        },

        _getCreateOptions: function () {
            return $.extend(true, {}, this.options);
        },

        _renderSegment: function (segmentId) {
            //this._createSegment(this.options.filters[filterId]);
        },

        _matchApplicable: function (applicable, criteria) {
            return _.find(applicable, function (item) {
                return _.every(item, function (value, key) {
                    return criteria[key] === value;
                });
            });
        },
//
//        _createSegment: function (options) {
//            var moduleName = mapFilterModuleName(options.type);
//
//            require([moduleName], _.bind(function (Filter) {
//                var filter = new (Filter.extend(options))();
//                this._appendFilter(filter);
//            }, this));
//        },

        _appendFilter: function (filter) {
            var value = this.element.data('value');
            this.filter = filter;

            if (value && value.criterion) {
                this.filter.value = value.criterion.data;
            }

            this.filter.render();
            this.$filterContainer.empty().append(this.filter.$el);

            this.filter.on('update', _.bind(this._onUpdate, this));
            this._onUpdate();
        },

        _onUpdate: function () {
            var value;

            if (!this.filter.isEmptyValue()) {
                value = {
                    columnName: this.element.find('input.select').select2('val'),
                    criterion: {
                        filter: this.filter.type,
                        data: this.filter.getValue()
                    }
                };
            } else {
                value = {};
            }

            this.element.data('value', value);
            this.element.trigger('changed');
        },

        selectSegment: function (name) {
            this.$segmentChoice.segmentChoice('setValue', name);
        }
    });

    return $;
});
