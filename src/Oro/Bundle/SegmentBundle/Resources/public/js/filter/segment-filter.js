/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'orofilter/js/filter/abstract-filter'
], function ($, _, __, AbstractFilter) {
    'use strict';

    /**
     * Segment filter
     *
     * @export  orosegment/js/filter/segment-filter
     * @class   orosegment.filter.SegmentFilter
     * @extends orofilter.filter.AbstractFilter
     */
    return AbstractFilter.extend({
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
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function (options) {
            _.extend(this, options);

            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: ''
                };
            }

            AbstractFilter.prototype.initialize.apply(this, arguments);
        },

        /**
         * Render filter template
         *
         * @return {*}
         */
        render: function ($segmentChoice) {
            var data = this.choices[this.getValue().value];
            data.text = data.label;
            data.id = 'segment_' + data.value;

            $segmentChoice.segmentChoice('setSelectedData', data);

            return this;
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.inputSelector, value.value);
            return this;
        },

        /**
         * @inheritDoc
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
            }
        }
    });
});
