define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/abstract-filter'
], function($, _, __, AbstractFilter) {
    'use strict';

    /**
     * Segment filter
     *
     * @export  orosegment/js/filter/segment-filter
     * @class   orosegment.filter.SegmentFilter
     * @extends oro.filter.AbstractFilter
     */
    const SegmentFilter = AbstractFilter.extend({
        /**
         * Filter selector template
         *
         * @property
         */
        templateSelector: '#segment-filter-template',

        /**
         * Selector for filter area
         *
         * @property
         */
        containerSelector: '.filter-segment',

        /**
         * Selector for close button
         *
         * @property
         */
        disableSelector: '.disable-filter',

        /**
         * Selector for select input element
         *
         * @property
         */
        inputSelector: 'select',

        /**
         * Filter events
         *
         * @property
         */
        events: {
        },

        /**
         * Filter choices
         */
        choices: {
        },

        /**
         * @inheritdoc
         */
        constructor: function SegmentFilter(options) {
            SegmentFilter.__super__.constructor.call(this, options);
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this, options);

            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: ''
                };
            }

            AbstractFilter.prototype.initialize.call(this, options);
        },

        render: function() {
            return this;
        },

        /**
         * @inheritdoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.inputSelector, value.value);
            return this;
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            return {
                value: this._getInputValue(this.inputSelector)
            };
        },

        getValue: function() {
            return {
                type: null,
                value: this.value.value
            };
        },

        getSelectedLabel: function() {
            return _.result(this.choices[this.value.value], 'label');
        }
    });

    return SegmentFilter;
});
