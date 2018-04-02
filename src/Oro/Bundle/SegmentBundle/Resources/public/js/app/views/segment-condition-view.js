define(function(require) {
    'use strict';

    // @export orosegment/js/app/views/segment-condition-view

    var SegmentConditionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var SegmentFilter = require('orosegment/js/filter/segment-filter');
    var AbstractConditionView = require('oroquerydesigner/js/app/views/abstract-condition-view');
    var SegmentChoiceView = require('orosegment/js/app/views/segment-choice-view');

    SegmentConditionView = AbstractConditionView.extend({
        /**
         * @inheritDoc
         */
        constructor: function SegmentConditionView() {
            SegmentConditionView.__super__.constructor.apply(this, arguments);
        },

        getDefaultOptions: function() {
            var defaultOptions = SegmentConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                segmentChoice: {}
            });
        },

        initChoiceInputView: function() {
            var choiceInput = new SegmentChoiceView(_.extend({
                autoRender: true,
                el: this.$choiceInput,
                entity: this.options.rootEntity
            }, this.options.segmentChoice));
            return $.when(choiceInput);
        },

        onChoiceInputReady: function(choiceInputView) {
            SegmentConditionView.__super__.onChoiceInputReady.call(this, choiceInputView);
            if (this.filter) {
                var filterValue = this._getFilterValue();
                var label = this.filter.getSelectedLabel();
                if (filterValue && label) {
                    choiceInputView.setData({
                        id: 'segment_' + filterValue.value,
                        text: label
                    });
                }
            }
        },

        _renderFilter: function(fieldId) {
            var segmentId = fieldId.split('_')[1];
            var filterId = this._getSegmentFilterId();

            var data = this.subview('choice-input').getData();
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
            var entity = this.subview('choice-input').entity;
            return this.filter ? _.result(this.filter.entity_ids, entity) : null;
        }
    });

    return SegmentConditionView;
});
