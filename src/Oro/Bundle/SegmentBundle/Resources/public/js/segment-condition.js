define(['jquery', 'underscore', './filter/segment-filter', './segment-choice', 'jquery-ui'],
function($, _, SegmentFilter) {
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

        _create: function() {
            var data = this.element.data('value');

            this.$segmentChoice = $('<input>').addClass(this.options.segmentChoiceClass);
            this.$filterContainer = $('<span>').addClass(this.options.filterContainerClass);
            this.element.append(this.$segmentChoice, this.$filterContainer);

            this.$segmentChoice.segmentChoice(this.options.segmentChoice);

            if (data && data.columnName) {
                this.selectSegment(data);
                this._renderFilter(data.columnName);
            }

            this.$segmentChoice.on('changed', _.bind(function(e, filterId) {
                $(':focus').blur();
                // reset current value on segment change
                this.element.data('value', {});
                this._renderFilter(filterId);
                e.stopPropagation();
            }, this));

            this.$filterContainer.on('change', _.bind(function() {
                if (this.filter) {
                    this.filter.applyValue();
                }
            }, this));
        },

        _getCreateOptions: function() {
            return $.extend(true, {}, this.options);
        },

        _renderFilter: function(fieldId) {
            var segmentId = fieldId.split('_')[1];
            var filterId = this._getSegmentFilterId();

            var data = this.element.find('input.select').inputWidget('valData');
            if (_.has(data, 'id')) {
                data.value = segmentId;
                // pre-set data
                this.element.data('value', {
                    criterion: {
                        filter: 'segment',
                        data: data
                    }
                });
            }

            var options = this.options.filters[filterId];
            this._createFilter(options);
        },

        /**
         * Find filter in metadata array and return it's index there
         *
         * @returns {*}
         * @private
         */
        _getSegmentFilterId: function() {
            var filterId = null;

            _.each(this.options.filters, function(filter, id) {
                if ('segment' === filter.name) {
                    filterId = id;
                }
            });

            return filterId;
        },

        /**
         * Creates filter instance
         *
         * @param options {Object}
         * @private
         */
        _createFilter: function(options) {
            var filter = new (SegmentFilter.extend(options))();
            this._appendFilter(filter);
        },

        _appendFilter: function(filter) {
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

        _onUpdate: function() {
            var value;

            if (!this.filter.isEmptyValue()) {
                value = {
                    columnName: this.filter.entity_ids[this.$segmentChoice.data('entity')],
                    criteria: 'condition-segment',
                    criterion: {
                        filter: this.filter.name,
                        data: this.filter.getValue()
                    }
                };
            } else {
                value = {};
            }

            this.element.data('value', value);
            this.element.trigger('changed');
        },

        selectSegment: function(data) {
            this.$segmentChoice.segmentChoice('setValue', data.columnName);
        }
    });

    return $;
});
