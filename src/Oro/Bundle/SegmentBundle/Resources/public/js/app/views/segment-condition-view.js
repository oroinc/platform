define(function(require) {
    'use strict';

    var SegmentConditionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var SegmentFilter = require('orosegment/js/filter/segment-filter');
    var AbstractConditionView = require('oroquerydesigner/js/app/views/abstract-condition-view');

    require('orosegment/js/segment-choice');

    SegmentConditionView = AbstractConditionView.extend({
        getDefaultOptions: function() {
            var defaultOptions = SegmentConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                segmentChoice: {}
            });
        },

        initChoiceInput: function() {
            this.$choiceInput.segmentChoice(this.options.segmentChoice);
        },

        getChoiceInputWidget: function() {
            return this.$choiceInput.segmentChoice('instance');
        },

        render: function() {
            SegmentConditionView.__super__.render.call(this);
            if (this.filter) {
                var filterValue = this._getFilterValue();
                var label = this.filter.getSelectedLabel();
                if (filterValue && label) {
                    this.getChoiceInputWidget().setSelectedData({
                        id: 'segment_' + filterValue.value,
                        text: label
                    });
                }
            }
        },

        _renderFilter: function(fieldId) {
            var segmentId = fieldId.split('_')[1];
            var filterId = this._getSegmentFilterId();

            var data = this.$choiceInput.inputWidget('data');
            if (_.has(data, 'id')) {
                data.value = segmentId;
                // pre-set data
                this.setValue({
                    criterion: {
                        filter: 'segment',
                        data: data
                    }
                });
            }

            var filterOptions = this.options.filters[filterId];
            var filter = new (SegmentFilter.extend(filterOptions))();
            this._appendFilter(filter);
            // There are no async operations so return a resolved promise just for consistency
            return $.Deferred().resolve().promise();
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

        _collectValue: function() {
            var value = SegmentConditionView.__super__._collectValue.call(this);

            if (!_.isEmpty(value)) {
                value.criteria = 'condition-segment';
            }

            return value;
        },

        getColumnName: function() {
            var entity = this.$choiceInput.data('entity');
            return this.filter ? _.result(this.filter.entity_ids, entity) : null;
        }
    });

    return SegmentConditionView;
});
