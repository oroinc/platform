/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    './select-filter'
], function (_, __, SelectFilter) {
    'use strict';

    var MultiSelectFilter;

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/multiselect-filter
     * @class   oro.filter.MultiSelectFilter
     * @extends oro.filter.SelectFilter
     */
    MultiSelectFilter = SelectFilter.extend({
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
        _onSelectChange: function () {
            MultiSelectFilter.__super__._onSelectChange.apply(this, arguments);
            this._setDropdownWidth();
        }
    });

    return MultiSelectFilter;
});
