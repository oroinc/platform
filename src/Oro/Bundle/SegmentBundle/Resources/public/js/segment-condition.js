/*global define, require*/
/*jslint nomen: true*/
define(['jquery', 'underscore', './filter/segment-filter', './segment-choice', 'jquery-ui'], function ($, _, SegmentFilter) {
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
                this.selectSegment(data);
                this._renderFilter();
            }

            this.$segmentChoice.on('changed', _.bind(function (e) {
                $(':focus').blur();
                // reset current value on segment change
                this.element.data('value', {});
                this._renderFilter();
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

        _renderFilter: function () {
            var filterId = this._getSegmentFilterId();

            var data = this.element.find('input.select').select2('data');
            if (_.has(data, 'id')) {
                // pre-set data
                this.element.data('value', {
                    criterion: {
                        filter: 'segment',
                        data:    data
                    }
                });
            }

            this._createFilter(this.options.filters[filterId]);
        },

        /**
         * Find filter in metadata array and return it's index there
         *
         * @returns {*}
         * @private
         */
        _getSegmentFilterId: function () {
            var filterId = null;

            _.each(this.options.filters, function (filter, id) {
                if ('segment' === filter.type) {
                    filterId = id;
                }
            });

            return filterId;
        },

        /**
         * Creates instance of segment filter
         *
         * @param options {Object}
         * @private
         */
        _createFilter: function (options) {
            var filter = new (SegmentFilter.extend(options))();
            this._appendFilter(filter);
        },

        _appendFilter: function (filter) {
            var value = this.element.data('value');
            this.filter = filter;

            if (value && value.criterion) {
                this.filter.value = value.criterion.data;
            }

            this.filter.render(this.$segmentChoice);
            this.$filterContainer.empty().append(this.filter.$el);

            this.filter.on('update', _.bind(this._onUpdate, this));
            this._onUpdate();
        },

        /**
         * On update evebt handler
         * @private
         */
        _onUpdate: function () {
            var value;

            if (!this.filter.isEmptyValue()) {
                value = {
                    columnName: 'id',
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

        selectSegment: function (data) {
            this.$segmentChoice.segmentChoice('setValue', data.columnName);
        }
    });

    return $;
});
