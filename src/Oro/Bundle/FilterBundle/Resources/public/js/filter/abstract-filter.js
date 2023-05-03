define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BaseView = require('oroui/js/app/views/base/view');
    const tools = require('oroui/js/tools');
    const FilterTemplate = require('orofilter/js/filter-template');
    const FilterHint = require('orofilter/js/filter-hint');
    const filterSettings = require('oro/filter-settings').default;
    let config = require('module-config').default(module.id);

    config = _.extend({
        placeholder: __('All'),
        labelPrefix: ''
    }, config);

    /**
     * Basic grid filter
     *
     * @export  oro/filter/abstract-filter
     * @class   oro.filter.AbstractFilter
     * @extends Backbone.View
     */
    const AbstractFilter = BaseView.extend(_.extend({}, FilterTemplate, {
        /**
         * Is filter renderable
         *
         * @property {Boolean}
         */
        renderable: false,

        /**
         * Is filter visible in UI
         *
         * @property {Boolean}
         */
        visible: true,

        /**
         * Is filter renderable by default
         *
         * @property {Boolean}
         */
        renderableByDefault: false,

        /**
         * Name of filter field
         *
         * @property {String}
         */
        name: 'input_name',

        /**
         * Placeholder for default value
         *
         * @property
         */
        placeholder: config.placeholder,

        /**
         * Label of filter
         *
         * @property {String}
         */
        label: __('Input Label'),

        /**
         * Label prefix of filter
         *
         * @property {String}
         */
        labelPrefix: config.labelPrefix,

        /**
         * Is filter label visible
         *
         * @property {Boolean}
         */
        showLabel: true,

        /**
         * Parent element active class
         *
         * @property {String}
         */
        buttonActiveClass: 'open-filter',

        /**
         * Criteria trigger expand class
         *
         * @property {String}
         */
        buttonExpandClass: filterSettings.buttonExpandClass,

        /**
         * Element enclosing a criteria dropdown
         *
         * @property {Array.<string|jQuery|HTMLElement>}
         */
        dropdownFitContainers: ['.ui-dialog-content>*:first-child', '#container', 'body'],

        /**
         * Allow clear selected value
         *
         * @property {Boolean}
         */
        allowClear: true,

        /**
         * Is used for states in template
         * @property {String} 'dropdown-mode' | 'toggle-mode'
         * @default ''
         */
        renderMode: '',

        /**
         * Separate container selector where filter hint will be placed
         *
         * @property {string}
         */
        outerHintContainer: void 0,

        /**
         * @inheritdoc
         */
        constructor: function AbstractFilter(options) {
            AbstractFilter.__super__.constructor.call(this, options);
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         * @param {Boolean} [options.renderable]
         */
        initialize: function(options) {
            const opts = _.pick(options || {}, 'renderable', 'visible', 'placeholder', 'showLabel', 'label',
                'templateSelector', 'templateTheme', 'template', 'renderMode', 'outerHintContainer');
            _.extend(this, opts);

            this._defineTemplate();

            this.renderableByDefault = this.renderable;

            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {};
            }
            // init raw value of filter if it was not initialized
            if (_.isUndefined(this.value)) {
                this.value = tools.deepClone(this.emptyValue);
            }

            AbstractFilter.__super__.initialize.call(this, options);

            const hintView = new FilterHint({
                filter: this
            });

            this.subview('hint', hintView);

            this.listenTo(hintView, 'reset', this.reset);
        },

        isRendered: function() {
            return this._rendereddInMode === this.renderMode;
        },

        rendered: function() {
            this._rendereddInMode = this.renderMode;
            this.trigger('rendered');
            return this;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.value;
            delete this.emptyValue;
            AbstractFilter.__super__.dispose.call(this);
        },

        /**
         * Enable filter
         *
         * @return {*}
         */
        enable: function() {
            if (!this.renderable) {
                this.renderable = true;
                this.show();
                this.trigger('enable', this);
            }
            return this;
        },

        /**
         * Disable filter
         *
         * @return {*}
         */
        disable: function() {
            if (this.renderable) {
                this.renderable = false;
                this.hide();
                this.trigger('disable', this);
                this.reset();
            }
            return this;
        },

        /**
         * Show filter
         *
         * @return {*}
         */
        show: function() {
            if (this.visible) {
                this.$el.show();
            }
            return this;
        },

        /**
         * Hide filter
         *
         * @return {*}
         */
        hide: function() {
            this.$el.hide();
            return this;
        },

        close: function() {},

        /**
         * Reset filter elements
         *
         * @return {*}
         */
        reset: function() {
            this.trigger('reset');
            this.setValue(this.emptyValue);
            return this;
        },

        /**
         * Get clone of current raw value
         *
         * @return {Object}
         */
        getValue: function() {
            return tools.deepClone(this.value);
        },

        /**
         * Set raw value to filter
         *
         * @param value
         * @return {*}
         */
        setValue: function(value) {
            if (!tools.isEqualsLoosely(this.value, value)) {
                const oldValue = this.value;
                this.value = tools.deepClone(value);
                this._updateDOMValue();
                this._onValueUpdated(this.value, oldValue);
            }
            return this;
        },

        /**
         * Set renderMode to filter
         * @param {String} value
         */
        setRenderMode: function(value) {
            if (_.isString(value) && value.length) {
                this.renderMode = value;
            }
        },

        /**
         * Find element that dropdown of filter should fit to
         *
         * @param {string|jQuery|HTMLElement} element
         * @return {*}
         */
        _findDropdownFitContainer: function(element) {
            element = element || this.$el;
            let $container = $();
            for (let i = 0; i < this.dropdownFitContainers.length && $container.length === 0; i += 1) {
                $container = $(element).closest(this.dropdownFitContainers[i]);
            }
            return $container.length === 0 ? null : $container;
        },

        /**
         * Converts a display value to raw format, e.g. decimal value can be displayed as "5,000,000.00"
         * but raw value is 5000000.0
         *
         * @param {*} value
         * @return {*}
         * @protected
         */
        _formatRawValue: function(value) {
            return value;
        },

        /**
         * Converts a raw value to display format, opposite to _formatRawValue
         *
         * @param {*} value
         * @return {*}
         * @protected
         */
        _formatDisplayValue: function(value) {
            return value;
        },

        /**
         * Triggers when filter value is updated
         *
         * @param {*} newValue
         * @param {*} oldValue
         * @protected
         */
        _onValueUpdated: function(newValue, oldValue) {
            this._updateCriteriaHint();
            this._triggerUpdate(newValue, oldValue);
        },

        /**
         * Updates criteria hint element with actual criteria hint value
         *
         * @protected
         * @return {*}
         */
        _updateCriteriaHint: function() {
            this.subview('hint').update(this._getCriteriaHint());
            this.$el.find('.filter-criteria-selector')
                .toggleClass('filter-default-value', this.isEmptyValue());
            return this;
        },

        /**
         * Triggers when filter value is changed
         *
         * @protected
         */
        _onValueChanged: function() {
            this.trigger('change');
        },

        /**
         * Triggers update event
         *
         * @param {*} newValue
         * @param {*} oldValue
         * @protected
         */
        _triggerUpdate: function(newValue, oldValue) {
            this.trigger('update');
        },

        /**
         * Compare values
         *
         * @param {*} newValue
         * @param {*} oldValue
         * @returns {boolean}
         */
        isUpdatable(newValue, oldValue) {
            return true;
        },

        /**
         * Compares current value with empty value
         *
         * @return {Boolean}
         */
        isEmpty: function() {
            return tools.isEqualsLoosely(this.getValue(), this.emptyValue);
        },

        /**
         * Determines whether a filter value is empty or not
         * Unlike isEmpty method this method should take in account only data values.
         * For example if a filter has a string value and comparison type, the comparison type
         * should be ignored in this method.
         *
         * @return {Boolean}
         */
        isEmptyValue: function() {
            if (_.has(this.emptyValue, 'value') && _.has(this.value, 'value')) {
                return tools.isEqualsLoosely(this.value.value, this.emptyValue.value);
            }
            return true;
        },

        /**
         * Gets input value. Radio inputs are supported.
         *
         * @param {String|Object} input
         * @return {*}
         * @protected
         */
        _getInputValue: function(input) {
            let result;
            const $input = this.$(input);
            switch ($input.attr('type')) {
                case 'radio':
                    $input.each(function() {
                        if ($(this).is(':checked')) {
                            result = $(this).val();
                        }
                    });
                    break;
                default:
                    result = $input.val();
            }
            return result;
        },

        /**
         * Sets input value. Radio inputs are supported.
         *
         * @param {String|Object} input
         * @param {String} value
         * @protected
         * @return {*}
         */
        _setInputValue: function(input, value) {
            const $input = this.$(input);
            switch ($input.attr('type')) {
                case 'radio':
                    $input.each(function() {
                        const $input = $(this);
                        if ($input.attr('value') === value) {
                            $input.prop('checked', true);
                            $input.click();
                        } else {
                            $input.prop('checked', false);
                        }
                    });
                    break;
                default:
                    $input.val(value);
            }
            return this;
        },

        /**
         * Updated DOM value with current display value
         *
         * @return {*}
         * @protected
         */
        _updateDOMValue: function() {
            return this._writeDOMValue(this._getDisplayValue());
        },

        /**
         * Get criteria hint value
         *
         * @return {String}
         */
        _getCriteriaHint: function() {
            return '';
        },

        /**
         * Get current value formatted to display format
         *
         * @return {*}
         * @protected
         */
        _getDisplayValue: function(...args) {
            const value = (args.length > 0) ? args[0] : this.getValue();
            return this._formatDisplayValue(value);
        },

        /**
         * Writes values from object into DOM elements
         *
         * @param {Object} value
         * @abstract
         * @protected
         * @return {*}
         */
        _writeDOMValue: function(value) {
            throw new Error('Method _writeDOMValue is abstract and must be implemented');
            // this._setInputValue(inputValueSelector, value.value);
            // return this
        },

        /**
         * Reads value of DOM elements into object
         *
         * @return {Object}
         * @protected
         */
        _readDOMValue: function() {
            throw new Error('Method _readDOMValue is abstract and must be implemented');
            // return { value: this._getInputValue(this.inputValueSelector) }
        },

        /**
         * Return true if DOM Value of filter is changed
         *
         * @returns {boolean}
         * @protected
         */
        _isDOMValueChanged: function() {
            throw new Error('Method _isDOMValueChanged is abstract and must be implemented');
        },

        /**
         * Set filter parent button class
         *
         * @param {Object} element
         * @param {Boolean} status
         * @protected
         */
        _setButtonPressed: function(element, status) {
            this._setButtonExpanded(status);

            if (status) {
                element.parent().addClass(this.buttonActiveClass);
            } else {
                element.parent().removeClass(this.buttonActiveClass);
            }
        },

        /**
         * Set filter button class
         *
         * @param {Boolean} state
         * @protected
         */
        _setButtonExpanded: function(state) {
            this.$('.filter-criteria-selector')
                .toggleClass(this.buttonExpandClass, state).attr('aria-expanded', state);
        },

        /**
         * Prevent submit of parent form if any.
         *
         * @param {Event} e
         * @private
         */
        _preventEnterProcessing: function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                e.stopPropagation();
            }
        },

        /**
         * Apply changes manually
         *
         * @public
         */
        applyValue: function() {
            this.setValue(this._formatRawValue(this._readDOMValue()));
        },

        getState: function() {
            return {
                label: this.label,
                hint: this._getCriteriaHint()
            };
        },

        /**
         * Get extra criteria classes dependent on render mode
         *
         * @return {string|undefined}
         */
        getCriteriaExtraClass() {
            if (_.isObject(filterSettings) && _.isObject(filterSettings.appearance)) {
                const mode = filterSettings.appearance[this.renderMode];

                return mode && typeof mode.criteriaClass === 'string'
                    ? mode.criteriaClass
                    : void 0;
            }

            return void 0;
        },

        /**
         * @return {Object}
         */
        getTemplateDataProps() {
            return {
                inputFieldAriaLabel: __('oro.filter.input_field.aria_label', {label: this.label}),
                choiceAriaLabel: __('oro.filter.select_field.aria_label', {label: this.label}),
                updateButtonAriaLabel: __('oro.filter.updateButton.aria_label', {
                    label: `${__('oro.filter.by')} ${this.label}`}
                )
            };
        },

        /**
         * Detect is filter has dropdown mode
         * If renderMode is empty equals `dropdown-mode`
         * @returns {boolean}
         */
        isDropdownRenderMode() {
            return this.renderMode === 'dropdown-mode';
        }
    }));

    return AbstractFilter;
});
