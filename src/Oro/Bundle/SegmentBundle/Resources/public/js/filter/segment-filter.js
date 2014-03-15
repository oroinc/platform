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
         * Selector for widget button
         *
         * @property
         */
        buttonSelector: '.select-filter-widget.ui-multiselect:first',

        /**
         * Selector for select input element
         *
         * @property
         */
        inputSelector: 'select',

        /**
         * Select widget object
         *
         * @property
         */
        selectWidget: null,

        /**
         * Minimum widget menu width, calculated depends on filter options
         *
         * @property
         */
        minimumWidth: null,

        /**
         * Select widget options
         *
         * @property
         */
        widgetOptions: {
            classes: 'segment-filter-widget'
        },

        /**
         * @property {Boolean}
         */
        contextSearch: true,

        /**
         * Filter events
         *
         * @property
         */
        events: {
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
        render: function () {
            return this;
        },

        /**
         * Get text for filter hint
         *
         * @param {Array} checkedItems
         * @protected
         */
        _getSelectedText: function(checkedItems) {
            return '';
        },

        /**
         * Get criteria hint value
         *
         * @return {String}
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            var choice = _.find(this.choices, function (c) {
                return (c.value == value.value);
            });
            return !_.isUndefined(choice) ? choice.label : this.placeholder;
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
        }
    });
});
