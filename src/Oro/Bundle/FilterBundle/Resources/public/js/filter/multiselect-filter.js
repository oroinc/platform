define(function(require) {
    'use strict';

    const FILTER_EMPTY_VALUE = '';

    const template = require('tpl-loader!orofilter/templates/filter/multiselect-filter.html');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const SelectFilter = require('oro/filter/select-filter');

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/multiselect-filter
     * @class   oro.filter.MultiSelectFilter
     * @extends oro.filter.SelectFilter
     */
    const MultiSelectFilter = SelectFilter.extend({
        /**
         * Filter selector template
         *
         * @property
         */
        template: template,
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
         * @inheritdoc
         */
        constructor: function MultiSelectFilter(options) {
            MultiSelectFilter.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: [FILTER_EMPTY_VALUE]
                };
            }
            MultiSelectFilter.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        _onSelectChange: function(e) {
            MultiSelectFilter.__super__._onSelectChange.call(this, e);
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
            const widget = this.selectWidget.getWidget();
            const requiredWidth = this.cachedMinimumWidth;
            // fix width
            widget.width(requiredWidth).css({
                minWidth: requiredWidth,
                maxWidth: requiredWidth
            });
        },

        /**
         * Set raw value to filter
         *
         * @param value
         * @return {*}
         */
        setValue: function(value) {
            const normValue = this._normalizeValue(tools.deepClone(value));
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
                    const indexOfEmptyOption = value.value.indexOf(FILTER_EMPTY_VALUE);
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
         * @inheritdoc
         */
        _getCriteriaHint: function(...args) {
            const value = (args.length > 0) ? this._getDisplayValue(args[0]) : this._getDisplayValue();
            const choices = this._getSelectedChoices(value, this.choices);

            return choices.length > 0 ? choices.join(', ') : this.placeholder;
        },

        /**
         * @inheritdoc
         */
        _formatRawValue(value) {
            const formatted = MultiSelectFilter.__super__._formatRawValue.call(this, value);

            return this._normalizeValue(formatted);
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
        },

        /**
         * @inheritdoc
         */
        _isDOMValueChanged: function() {
            const thisDOMValue = this._readDOMValue();
            return (
                !_.isUndefined(thisDOMValue.value) &&
                _.isArray(thisDOMValue.value) &&
                !_.isEqual(this.value, thisDOMValue)
            );
        }
    });

    return MultiSelectFilter;
});
