define([
    'jquery',
    'routing',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    './abstract-filter'
], function($, routing, _, __, tools, AbstractFilter) {
    'use strict';

    // @const
    var FILTER_EMPTY_VALUE = '';

    var DictionaryFilter;

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/multiselect-filter
     * @class   oro.filter.MultiSelectFilter
     * @extends oro.filter.SelectFilter
     */
    DictionaryFilter = AbstractFilter.extend({
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
         * Minimal width of dropdown
         *
         * @private
         */
        minimumDropdownWidth: 120,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            console.log(5);
            console.log(this);

            this.initFilter();
            //if (_.isUndefined(this.emptyValue)) {
            //    this.emptyValue = {
            //        value: [FILTER_EMPTY_VALUE]
            //    };
            //}
            //DictionaryFilter.__super__.initialize.apply(this, arguments);
        },


        initFilter: function() {
            var className = this.constructor.prototype;
            var self = this;
            $.ajax({
                url: routing.generate(
                    'oro_api_get_dictionary_value_count',
                    {dictionary: className.filterParams.class.replace(/\\/g, '_'), limit: -1}
                ),
                success: function(data) {
                    self.count = data;
                    DictionaryFilter.__super__.initialize.apply(self, arguments);
                    if (data > 10) {
                        //self.initSelect2();
                        alert(1);
                    } else {
                        alert(2);
                        self.initMultiselect();
                    }
                },
                error: function(jqXHR) {
                    //messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                    //if (errorCallback) {
                    //    errorCallback(jqXHR);
                    //}
                }
            });
        },

        initMultiselect: function() {

        },

        /**
         * @inheritDoc
         */
        _onSelectChange: function() {
            DictionaryFilter.__super__._onSelectChange.apply(this, arguments);
            this._setDropdownWidth();
        },

        /**
         * Set design for select dropdown
         *
         * @protected
         */
        _setDropdownWidth: function() {
            if (!this.cachedMinimumWidth) {
                this.cachedMinimumWidth = Math.max(this.minimumDropdownWidth,
                    this.selectWidget.getMinimumDropdownWidth()) + 24;
            }
            var widget = this.selectWidget.getWidget();
            var requiredWidth = this.cachedMinimumWidth;
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
        setValue: function(value) {
            var normValue;
            normValue = this._normalizeValue(tools.deepClone(value));
            // prevent uncheck 'Any' value
            if ((value.value === null || value.value === undefined) && tools.isEqualsLoosely(this.value, normValue)) {
                this._updateDOMValue();
                this._onValueUpdated(normValue, this.value);
            }
            DictionaryFilter.__super__.setValue.call(this, normValue);
        },

        /**
         * Updates checkboxes when user clicks on element corresponding empty value
         */
        _normalizeValue: function(value) {
            // means that all checkboxes are unchecked
            if (value.value === null || value.value === undefined) {
                value.value = [FILTER_EMPTY_VALUE];
                return value;
            }
            // if we have old value
            // need to check if it has selected "EMPTY" option
            if (this.isEmpty()) {
                // need to uncheck it in new value
                if (value.value.length > 1) {
                    var indexOfEmptyOption = value.value.indexOf(FILTER_EMPTY_VALUE);
                    if (indexOfEmptyOption !== -1) {
                        value.value.splice(indexOfEmptyOption, 1);
                    }
                }
            } else {
                // if we just selected "EMPTY" option
                if (!value.value || value.value.indexOf(FILTER_EMPTY_VALUE) !== -1) {
                    // clear other choices
                    value.value = [FILTER_EMPTY_VALUE];
                }
            }
            return value;
        }
    });

    return DictionaryFilter;
});
