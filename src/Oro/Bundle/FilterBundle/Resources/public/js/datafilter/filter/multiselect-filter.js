/* global define */
define(['underscore', 'oro/translator', 'oro/datafilter/select-filter'],
function(_, __, SelectFilter) {
    'use strict';

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/datafilter/multiselect-filter
     * @class   oro.datafilter.MultiSelectFilter
     * @extends oro.datafilter.SelectFilter
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
