define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    './select-filter'
], function(_, __, tools, SelectFilter) {
    'use strict';

    // @const
    var FILTER_EMPTY_VALUE = '';

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
         * Minimal width of dropdown
         *
         * @private
         */
        minimumDropdownWidth: 120,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: [FILTER_EMPTY_VALUE]
                };
            }
            MultiSelectFilter.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _onSelectChange: function() {
            MultiSelectFilter.__super__._onSelectChange.apply(this, arguments);
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
            MultiSelectFilter.__super__.setValue.call(this, normValue);
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
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            var choices = this._getSelectedChoices(value, this.choices);

            return choices.length > 0 ? choices.join(', ') : this.placeholder;
        },

        /**
         * @param {Array} value
         * @param {Array} choices
         * @returns {Array}
         */
        _getSelectedChoices: function(value, choices) {
            return _.reduce(
                choices,
                function(result, choice) {
                    if (_.has(choice, 'choices')) {
                        return result.concat(this._getSelectedChoices(value, choice.choices));
                    }

                    if (_.indexOf(value.value, choice.value) !== -1) {
                        result.push(choice.label);
                    }

                    return result;
                },
                [],
                this
            );
        }
    });

    return MultiSelectFilter;
});
