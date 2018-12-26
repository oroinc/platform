define(function(require) {
    'use strict';

    /**
     * Multi currency cell content editor.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrids:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Mapped by number frontend type
     *       {column-name-1}:
     *         frontend_type: <multi-currency>
     *         multicurrency_config:
     *           original_field: '<original_field>'
     *           value_field: '<value_field>'
     *           currency_field: '<currency_field>'
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         inline_editing:
     *           editor:
     *             view: orocurrency/js/app/views/editor/multi-currency-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *           validation_rules:
     *             NotBlank: ~
     *         multicurrency_config:
     *           original_field: '<original_field>'
     *           value_field: '<value_field>'
     *           currency_field: '<currency_field>'
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.validation_rules | Optional. Validation rules. See [documentation](../../../../FormBundle/Resources/doc/reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * multicurrency_config.original_field | Field that contains combined currency value, like EUR100.0000
     * multicurrency_config.value_field | Field that contains amount of currency value
     * multicurrency_config.currency_field | Field that contains code of currency (e.g. EUR)
     *
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../../../../FormBundle/Resources/doc/reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {Object} options.choices - Array of codes of available currencies
     *
     * @augments [TextEditorView](../../../../FormBundle/Resources/doc/editor/text-editor-view.md)
     * @exports MultiCurrencyEditorView
     */
    var MultiCurrencyEditorView;
    var TextEditorView = require('oroform/js/app/views/editor/text-editor-view');
    var _ = require('underscore');
    var $ = require('jquery');
    var localeSettings = require('orolocale/js/locale-settings');
    var numberFormatter = require('orolocale/js/formatter/number');
    var multiCurrencyFormatter = require('orocurrency/js/formatter/multi-currency');
    require('jquery.select2');

    MultiCurrencyEditorView = TextEditorView.extend(/** @lends MultiCurrencyEditorView.prototype */{
        /**
         * Option for select2 widget to show or hide search input for list of currencies
         * @protected
         */
        MINIMUM_RESULTS_FOR_SEARCH: 8,

        className: function() {
            var classes = ['multi-currency-editor'];
            if (this.isSingleCurrency()) {
                classes.push('multi-currency-editor__single-currency');
            } else {
                classes.push('multi-currency-editor__multi-currency');
            }
            return classes.join(' ');
        },

        template: require('tpl!../../../../templates/multi-currency-editor.html'),

        availableCurrencies: [],

        _currencySelectionIsOpen: false,

        _isSelection: false,

        events: {
            'select2-opening input[name=currency]': 'onCurrencySelectOpening',
            'select2-open input[name=currency]': 'onCurrencySelectOpen',
            'select2-close input[name=currency]': 'onCurrencySelectClose',
            'change input[name=currency]': 'onChange'
        },

        constructor: function MultiCurrencyEditorView(options) {
            this.availableCurrencies = options.choices;
            MultiCurrencyEditorView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            MultiCurrencyEditorView.__super__.render.call(this);
            var _this = this;
            if (this.isSingleCurrency()) {
                return;
            }
            var select2options = {
                selectOnBlur: false,
                openOnEnter: false,
                minimumResultsForSearch: this.MINIMUM_RESULTS_FOR_SEARCH,
                dropdownCssClass: 'inline-editor__select2-drop',
                dontSelectFirstOptionOnOpen: true,
                data: {results: this.getCurrencyData()}
            };
            this.$('input[name=currency]').inputWidget('create', 'select2', {initializeOptions: select2options});
            this.$('.select2-focusser').on('keydown' + this.eventNamespace(), _.bind(function(e) {
                this.onGenericEnterKeydown(e);
            }, this));

            this.$('input.select2-input').bindFirst('keydown' + this.eventNamespace(), function(e) {
                var currencyPrestine = _this._currencyPrestine;
                _this._currencyPrestine = false;
                switch (e.keyCode) {
                    case _this.ENTER_KEY_CODE:
                        if (currencyPrestine || !_this._currencySelectionIsOpen) {
                            e.stopImmediatePropagation();
                            e.preventDefault();
                            _this.$('input[name=currency]').inputWidget('close');
                            _this.onGenericEnterKeydown(e);
                        }
                        break;
                    case _this.TAB_KEY_CODE:
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        _this.$('input[name=currency]').inputWidget('close');
                        _this.onGenericTabKeydown(e);
                        break;
                }
                _this.onGenericArrowKeydown(e);
            });
        },
        /**
         * Convert string presetation of value to object with 'currency' and 'amount' fields
         *
         * @param {String} value in format 'currency_code+amount'
         * @returns {Object}
         */
        parseRawValue: function(raw) {
            return multiCurrencyFormatter.unformatMultiCurrency(raw);
        },

        /**
         * Collects values from DOM elements and converts them to string format like EUR100.0000
         *
         * @returns {String}
         */
        getValue: function() {
            var $currency = this.$('input[name=currency]');
            var currency = $currency.data('select2') ? $currency.select2('val') : $currency.val();
            var amount = this.$('input[name=value]').val();
            var value = numberFormatter.unformatStrict(amount);
            if (amount.length === 0 || isNaN(value)) {
                return '';
            }
            return currency + value;
        },

        getValidationRules: function() {
            var rules = MultiCurrencyEditorView.__super__.getValidationRules.call(this);
            rules.Number = true;
            return rules;
        },

        /**
         * Prepares array of objects that presents select options in dropdown
         *
         * @returns {Array}
         */
        getCurrencyData: function() {
            var useSymbol = localeSettings.getCurrencyViewType() === 'symbol';
            var availableCurrencies = this.availableCurrencies;
            var modelCurrency = _.result(this.getModelValue(), 'currency');
            if (modelCurrency && _.indexOf(availableCurrencies, modelCurrency) === -1) {
                availableCurrencies = availableCurrencies.concat(modelCurrency);
            }
            var data = _.map(availableCurrencies, function(code) {
                return {
                    id: code,
                    text: useSymbol ? localeSettings.getCurrencySymbol(code) : code
                };
            });
            return data;
        },

        formatRawValue: function(value) {
            var result = this.parseRawValue(value);
            result.amount = result.amount === null ? '' : numberFormatter.formatMonetary(result.amount);
            result.currency = result.currency.length === 3 ? result.currency : localeSettings.getCurrency();
            return result;
        },

        isChanged: function() {
            var value = this.parseRawValue(this.getValue());
            var modelValue = this.getModelValue();
            return value.amount !== modelValue.amount || value.currency !== modelValue.currency;
        },

        onFocusout: function(e) {
            if (!this._currencySelectionIsOpen && !this._isSelection) {
                _.defer(_.bind(function() {
                    if (!this.disposed && !$.contains(this.el, document.activeElement)) {
                        MultiCurrencyEditorView.__super__.onFocusout.apply(this, arguments);
                    }
                }, this));
            }
        },

        onCurrencySelectOpening: function() {
            this._currencySelectionIsOpen = true;
            this._currencyPrestine = true;
        },

        onCurrencySelectOpen: function(e) {
            var select2 = this.$(e.target).data('select2');
            if (select2) {
                select2.dropdown.on('mousedown' + this.eventNamespace(), _.bind(function() {
                    this._isSelection = true;// to suppress focusout event
                }, this));
                select2.dropdown.on('mouseup' + this.eventNamespace(), _.bind(function() {
                    this._isSelection = false;
                }, this));
            }
        },

        onCurrencySelectClose: function(e) {
            this._currencySelectionIsOpen = false;
            var select2 = this.$(e.target).data('select2');
            if (!select2) {
                return;
            }
            select2.dropdown.off('mousedown' + this.eventNamespace() + ' mouseup' + this.eventNamespace());
        },

        getTemplateData: function() {
            var data = MultiCurrencyEditorView.__super__.getTemplateData.apply(this, arguments);
            data.singleCurrency = this.isSingleCurrency();
            data.currentCurrencyLabel = localeSettings.getCurrencyViewType() === 'symbol'
                ? localeSettings.getCurrencySymbol(data.value.currency)
                : data.value.currency;
            return data;
        },

        isSingleCurrency: function() {
            var currencyData = this.getCurrencyData();
            return (currencyData.length < 2);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.select2-focusser').off(this.eventNamespace());
            this.$('input[name=currency]').inputWidget('dispose');
            MultiCurrencyEditorView.__super__.dispose.call(this);
        }
    }, {
        processMetadata: function(columnMetadata) {
            if (_.isUndefined(columnMetadata.choices)) {
                throw new Error('`choices` is required option');
            }
            if (!columnMetadata.inline_editing.editor.view_options) {
                columnMetadata.inline_editing.editor.view_options = {};
            }
            columnMetadata.inline_editing.editor.view_options.choices = columnMetadata.choices;
        }
    });

    return MultiCurrencyEditorView;
});
