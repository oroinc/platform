/*global define*/
define(['underscore', 'oro/translator', './select-filter'
    ], function (_, __, SelectFilter) {
    'use strict';

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  orofilter/js/filter/multiselect-filter
     * @class   orofilter.filter.MultiSelectFilter
     * @extends orofilter.filter.SelectFilter
     */
    return SelectFilter.extend({
        /**
         * Filter selector template
         *
         * @property
         */
        templateSelector: '#multiselect-filter-template',

        /**
         * Select widget options
         *
         * @property
         */
        widgetOptions: {
            multiple: true,
            classes: 'select-filter-widget multiselect-filter-widget'
        },

        /**
         * @inheritDoc
         */
        _onSelectChange: function() {
            SelectFilter.prototype._onSelectChange.apply(this, arguments);
            this._setDropdownWidth();
        }
    });
});
