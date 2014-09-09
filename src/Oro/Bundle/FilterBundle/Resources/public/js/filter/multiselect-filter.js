/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    './select-filter'
], function (_, __, tools, SelectFilter) {
    'use strict';

    // @const
    var FILTER_EMPTY_VALUE = "";

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
        },

        /**
         * Minimal width of dropdown
         *
         * @private
         */
        minimumDropdownWidth: 120,

        /**
         * Set design for select dropdown
         *
         * @protected
         */
        _setDropdownWidth: function () {
            if (!this.cachedMinimumWidth) {
                this.cachedMinimumWidth = Math.max(this.minimumDropdownWidth, this.selectWidget.getMinimumDropdownWidth()) + 24;
            }
            var widget = this.selectWidget.getWidget(),
                requiredWidth = this.cachedMinimumWidth;
            // fix width
            widget.width(requiredWidth).css({
                minWidth: requiredWidth,
                maxWidth: requiredWidth
            });
            widget.find('input[type="search"]').width(requiredWidth - 30);
        },

        /**
         * Set raw value to filter
         *
         * @param value
         * @return {*}
         */
        setValue: function (value) {
            var oldValue = this.value;

            this.value = this._checkValue(tools.deepClone(value), oldValue);

            // update checkboxes state always
            this._onValueUpdated(this.value, oldValue);
            this._updateDOMValue();

            return this;
        },

        /**
         * Updates checkboxes when user clicks on element corresponding empty value
         */
        _checkValue: function (newValue, oldValue) {
            // means that  all checkboxes are unchecked
            if (newValue.value == null) {
                newValue.value = [FILTER_EMPTY_VALUE];
                return newValue;
            }
            // if we have old value
            // need to check if it has selected "EMPTY" option
            if (
                    oldValue.value == FILTER_EMPTY_VALUE
                    || (oldValue.value.indexOf && oldValue.value.indexOf(FILTER_EMPTY_VALUE) != -1)
            ) {
                // need to uncheck it in new value
                if (newValue.value.length > 1) {
                    var indexOfEmptyOption = newValue.value.indexOf(FILTER_EMPTY_VALUE);
                    if (indexOfEmptyOption != -1) {
                        newValue.value.splice(indexOfEmptyOption, 1);
                    }
                }
            } else {
                // if we just selected "EMPTY" option
                if (!newValue.value || newValue.value.indexOf(FILTER_EMPTY_VALUE) != -1) {
                    // clear other choices
                    newValue.value = [FILTER_EMPTY_VALUE];
                }
            }
            return newValue;
        },

        /**
         * @inheritDoc
         */
        _onValueUpdated: function (newValue, oldValue) {
            SelectFilter.__super__._onValueUpdated.apply(this, arguments);

            this.selectWidget.multiselect('refresh');
        }

    });

    return MultiSelectFilter;
});
