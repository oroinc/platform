define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const SegmentFilter = require('orosegment/js/filter/segment-filter');
    const AbstractConditionView = require('oroquerydesigner/js/app/views/abstract-condition-view');
    const SegmentChoiceView = require('orosegment/js/app/views/segment-choice-view');

    const SegmentConditionView = AbstractConditionView.extend({
        /**
         * @inheritdoc
         */
        constructor: function SegmentConditionView(options) {
            SegmentConditionView.__super__.constructor.call(this, options);
        },

        getDefaultOptions: function() {
            const defaultOptions = SegmentConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                segmentChoice: {}
            });
        },

        initChoiceInputView: function() {
            const choiceInput = new SegmentChoiceView(_.extend({
                autoRender: true,
                el: this.$choiceInput,
                entity: this.options.rootEntity
            }, this.options.segmentChoice));
            return $.when(choiceInput);
        },

        onChoiceInputReady: function(choiceInputView) {
            SegmentConditionView.__super__.onChoiceInputReady.call(this, choiceInputView);
            if (this.filter) {
                const filterValue = this._getFilterValue();
                const label = this.filter.getSelectedLabel();
                if (filterValue && label) {
                    choiceInputView.setData({
                        id: 'segment_' + filterValue.value,
                        text: label
                    });
                }
            }
        },

        _renderFilter: function(fieldId) {
            const segmentId = fieldId.split('_')[1];
            const filterId = this._getSegmentFilterId();

            const data = this.subview('choice-input').getData();
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

            const filterOptions = this.options.filters[filterId];
            const filter = new (SegmentFilter.extend(filterOptions))();
            this._appendFilter(filter);
        },

        /**
         * Find filter in metadata array and return it's index there
         *
         * @returns {*}
         * @private
         */
        _getSegmentFilterId: function() {
            let filterId = null;

            _.each(this.options.filters, function(filter, id) {
                if ('segment' === filter.name) {
                    filterId = id;
                }
            });

            return filterId;
        },

        _collectValue: function() {
            const value = SegmentConditionView.__super__._collectValue.call(this);

            if (!_.isEmpty(value)) {
                value.criteria = 'condition-segment';
            }

            return value;
        },

        getColumnName: function() {
            const entity = this.subview('choice-input').entity;
            return this.filter ? _.result(this.filter.entity_ids, entity) : null;
        }
    });

    return SegmentConditionView;
});
