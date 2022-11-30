define(function(require) {
    'use strict';

    const template = require('tpl-loader!orofilter/templates/filter/dictionary-filter.html');
    const fieldTemplate = require('tpl-loader!orofilter/templates/filter/select-field.html');
    const $ = require('jquery');
    const routing = require('routing');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const ChoiceFilter = require('oro/filter/choice-filter');
    const tools = require('oroui/js/tools');
    require('jquery.select2');

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/dictionary-filter
     * @class   oro.filter.DictionaryFilter
     * @extends oro.filter.ChoiceFilter
     */
    const DictionaryFilter = ChoiceFilter.extend({
        /* eslint-disable quote-props */
        /**
         * select2 will apply to element with this selector
         */
        elementSelector: '.select-values-autocomplete',

        /**
         * Filter selector template
         *
         * @property
         */
        template: template,
        templateSelector: '#dictionary-filter-template',

        /**
         * Template selector for dictionary field parts
         *
         * @property
         */
        fieldTemplate: fieldTemplate,
        fieldTemplateSelector: '#select-field-template',

        /**
         * Maximum value of count items for drop down menu.
         * If count values will be bigger than this value then
         * this filter will use select2 with autocomplete
         */
        maxCountForDropDownMode: 10,

        /**
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'input[type="hidden"]:last',
            value: 'input.select-values-autocomplete'
        },

        filterParams: null,

        'class': null,

        select2ConfigData: null,

        isInitSelect2: false,

        previousData: [],

        /**
         * Data of selected values
         */
        selectedData: {},

        /**
         * Route name for dictionary values filter
         */
        dictionaryValueRoute: 'oro_dictionary_value',

        /**
         * Route name for dictionary search
         */
        dictionarySearchRoute: 'oro_dictionary_search',

        /**
         * @inheritdoc
         */
        constructor: function DictionaryFilter(options) {
            DictionaryFilter.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            // Each filter should have own copy,
            // otherwise 2 filters on same page will show same values
            this.selectedData = {};

            if (this.filterParams) {
                this.dictionaryClass = this.filterParams.class.replace(/\\/g, '_');
            } else {
                this.dictionaryClass = this.class.replace(/\\/g, '_');
            }

            this.listenTo(this, 'renderCriteriaLoadValues', this.renderCriteriaLoadValues);
            this.listenTo(this, 'updateCriteriaLabels', this.updateCriteriaLabels);

            DictionaryFilter.__super__.initialize.call(this, options);
        },

        _toggleSelect2Element: function() {
            const container = this.$(this.criteriaSelector);
            const type = container.find(this.criteriaValueSelectors.type).val();
            const select2element = this.$el.find(this.elementSelector);

            if (this.isEmptyType(type)) {
                // see original _handleEmptyFilter
                select2element.hide();
                select2element.inputWidget('val', '');
            } else {
                select2element.show();
            }
        },

        /**
         * @inheritdoc
         */
        _updateValueFieldVisibility: function() {
            this._toggleSelect2Element();
            return DictionaryFilter.__super__._updateValueFieldVisibility.call(this);
        },

        /**
         * Handle empty filter selection
         *
         * @protected
         */
        _handleEmptyFilter: function() {
            this._toggleSelect2Element();
            return DictionaryFilter.__super__._handleEmptyFilter.call(this);
        },

        /**
         * @inheritdoc
         */
        reset: function() {
            DictionaryFilter.__super__.reset.call(this);
            const select2element = this.$el.find(this.elementSelector);
            const data = select2element.inputWidget('data');
            if (data) {
                this.previousData = data;
            }
            select2element.inputWidget('data', null);
        },

        resetFags() {
            this.popupCriteriaShowed = false;
            this.selectDropdownOpened = false;
            this._criteriaRenderd = false;
            this._isRenderingInProgress = false;
        },

        /**
         * Init render
         */
        render: function() {
            this.resetFags();
            this.renderDeferred = $.Deferred();
            this._wrap('');
            if (this.$el.html() === '') {
                this._renderCriteria();
            }
        },

        /**
         * Execute ajax request to get data of entities by ids.
         *
         * @param successEventName
         */
        loadValuesById: function(successEventName) {
            const self = this;

            if (this.select2ConfigData === null) {
                const $container = self.$(self.elementSelector).parent();

                $container.addClass('loading');

                $.ajax({
                    url: routing.generate(
                        self.dictionaryValueRoute,
                        {
                            dictionary: this.dictionaryClass
                        }
                    ),
                    data: {
                        keys: this.isEmptyType(this.value.type) ? [] : this.value.value
                    },
                    success: function(response) {
                        $container.removeClass('loading');

                        self.trigger(successEventName, response);
                    }
                });
            } else {
                const select2ConfigData = this.select2ConfigData;
                const value = this.value.value;
                const result = {
                    results: _.filter(select2ConfigData, function(item) {
                        const id = item.id.toString();
                        return _.indexOf(value, id) !== -1;
                    })
                };
                self.trigger(successEventName, result);
            }
        },

        /**
         * Handler for event 'renderCriteriaLoadValues'
         *
         * @param response
         */
        renderCriteriaLoadValues: function(response) {
            this.updateLocalValues(response.results);

            this._writeDOMValue(this.value);
            this.applySelect2();
            this._updateCriteriaHint();
            this._updateDOMValue();
            this._handleEmptyFilter();
            this.renderDeferred.resolve();
            this.trigger('update');
        },

        /**
         * Handler for event 'updateCriteriaLabels'
         *
         * @param response
         */
        updateCriteriaLabels: function(response) {
            this.updateLocalValues(response.results);
            this.$(this.elementSelector).inputWidget('data', this.getDataForSelect2());
            this._updateCriteriaHint();
            this.trigger('update');
        },

        /**
         * Update privet variables selectedData and value
         *
         * @param values
         *
         * @returns {oro.filter.DictionaryFilter}
         */
        updateLocalValues: function(values) {
            const ids = [];
            _.each(values, function(item) {
                ids.push(item.id);
                this.selectedData[item.id] = item;
            }, this);

            this.value.value = ids;

            return this;
        },

        /**
         * @inheritdoc
         */
        _renderCriteria: function() {
            this.renderTemplate();
            this.loadValuesById('renderCriteriaLoadValues');
        },

        /**
         * Render template for filter
         */
        renderTemplate: function() {
            const value = _.extend({}, this.emptyValue, this.value);
            let selectedChoiceLabel = '';
            if (!_.isEmpty(this.choices)) {
                const foundChoice = _.find(this.choices, function(choice) {
                    return value.type === choice.value;
                });
                selectedChoiceLabel = foundChoice.label;
            }
            const parts = this._getParts();

            const $filter = $(this.template({
                parts: parts,
                isEmpty: false,
                showLabel: this.showLabel,
                label: this.label,
                selectedChoiceLabel: selectedChoiceLabel,
                selectedChoice: value.type,
                choices: this.choices,
                name: this.name,
                renderMode: this.renderMode,
                ...this.getTemplateDataProps()
            }));

            this._appendFilter($filter);
        },

        /**
         * init select2 for input
         */
        applySelect2: function() {
            const self = this;
            const select2Config = this.getSelect2Config();
            const select2element = this.$el.find(this.elementSelector);
            const values = this.getDataForSelect2();

            select2element.removeClass('hide');
            select2element.attr('multiple', 'multiple');
            select2element.inputWidget('create', 'select2', {initializeOptions: select2Config});
            self.isInitSelect2 = true;
            if (this.templateTheme) {
                select2element.on('change', function() {
                    self.applyValue();
                });
            }
            select2element.inputWidget('data', values);
            this._criteriaRenderd = true;

            this._alignCriteria();

            if (this.autoClose !== false) {
                this._focusCriteriaValue();
            }
        },

        /**
         * Return config for select2
         */
        getSelect2Config: function() {
            const config = {
                multiple: true,
                containerCssClass: 'dictionary-filter',
                dropdownAutoWidth: true,
                minimumInputLength: 0,
                placeholder: __('Choose values')
            };

            if (this.select2ConfigData === null) {
                config.ajax = {
                    url: routing.generate(
                        this.dictionarySearchRoute,
                        {
                            dictionary: this.dictionaryClass
                        }
                    ),
                    dataType: 'json',
                    delay: 250,
                    type: 'POST',
                    data: function(params) {
                        return {
                            q: params // search term
                        };
                    },
                    results: function(data) {
                        return {
                            results: data.results
                        };
                    }
                };
            } else {
                config.data = {
                    results: this.select2ConfigData
                };

                if (config.data.results.length > 100) {
                    config.minimumInputLength = 2;
                }
            }

            if (this.templateTheme === '') {
                config.width = 'off';
            }

            return config;
        },

        /**
         * Convert data to format for select2
         *
         * @returns {Array}
         */
        getDataForSelect2: function() {
            const values = [];
            _.each(this.value.value, function(value) {
                const item = this.selectedData[value];

                if (item) {
                    values.push({
                        id: item.id,
                        text: item.text
                    });
                }
            }, this);

            return values;
        },

        /**
         * @inheritdoc
         */
        isEmptyValue: function() {
            if (this.isEmptyType(this.value.type)) {
                return false;
            }
            const value = this.getValue();

            return !value.value || value.value.length === 0;
        },

        /**
         * @inheritdoc
         */
        _getParts: function() {
            const value = _.extend({}, this.emptyValue, this.getValue());
            const dictionaryPartTemplate = this._getTemplate('fieldTemplate');
            const parts = [];
            const selectedPartLabel = this._getSelectedChoiceLabel('choices', this.value);
            // add date parts only if embed template used
            if (this.templateTheme !== '') {
                parts.push(
                    dictionaryPartTemplate({
                        name: this.name + '_part',
                        choices: this.choices,
                        selectedChoice: value.type,
                        selectedChoiceLabel: selectedPartLabel
                    })
                );
            }

            return parts;
        },

        /**
         * Set raw value to filter
         *
         * @param value
         *
         * @return {*}
         */
        setValue: function(value) {
            this.preloadSelectedData(value);

            const oldValue = this.value;
            this.value = tools.deepClone(value);
            this.$(this.elementSelector).inputWidget('data', this.getDataForSelect2());
            this._updateDOMValue();

            if (this.valueIsLoaded(value.value) || this.isEmptyType(value.type)) {
                this._onValueUpdated(this.value, oldValue);
            } else {
                this.loadValuesById('updateCriteriaLabels');
            }

            return this;
        },

        /**
         * Preloads selectedData with available data from select2 so that we don't have to
         * make additional requests
         */
        preloadSelectedData: function(value) {
            if (!this.isInitSelect2 || !value.value) {
                return;
            }

            const data = this.$(this.elementSelector).inputWidget('data');
            _.each(data, function(elem) {
                if (!('id' in elem)) {
                    return;
                }

                if (this.selectedData[elem.id]) {
                    return;
                }

                this.selectedData[elem.id] = elem;
            }, this);
        },

        /**
         * @inheritdoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.type, value.type);
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            let value;
            if (this.isInitSelect2) {
                value = this.$el.find('.select-values-autocomplete').inputWidget('val');
            } else {
                value = null;
            }
            return {
                type: this._getInputValue(this.criteriaValueSelectors.type),
                value: value
            };
        },

        /**
         * @inheritdoc
         */
        _getSelectedChoiceLabel: function(property, value) {
            let selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                const foundChoice = _.find(this[property], function(choice) {
                    return (choice.value === value.type);
                });

                if (foundChoice) {
                    selectedChoiceLabel = foundChoice.label;
                }
            }

            return selectedChoiceLabel;
        },

        /**
         * @inheritdoc
         */
        _getCriteriaHint: function() {
            const value = this._getDisplayValue();
            let option = null;

            if (!_.isUndefined(value.type)) {
                const type = value.type;
                option = this._getChoiceOption(type);

                if (this.isEmptyType(type)) {
                    return option ? option.label : this.placeholder;
                }
            }

            if (!value.value || value.value.length === 0) {
                return this.placeholder;
            }

            if (this.valueIsLoaded(value.value)) {
                const self = this;

                const hintRawValue = _.isObject(_.first(value.value))
                    ? _.map(value.value, _.property('text'))
                    : _.chain(value.value)
                        .map(function(id) {
                            const item = _.find(self.selectedData, function(item) {
                                return item.id.toString() === id.toString();
                            });

                            return item ? item.text : item;
                        })
                        .filter(_.negate(_.isUndefined))
                        .value();

                const hintValue = this.wrapHintValue ? ('"' + hintRawValue + '"') : hintRawValue;

                return (option ? option.label + ' ' : '') + hintValue;
            } else {
                return this.placeholder;
            }
        },

        /**
         * @inheritdoc
         */
        _hideCriteria: function() {
            this.$el.find(this.elementSelector).inputWidget('close');
            DictionaryFilter.__super__._hideCriteria.call(this);
        },

        /**
         * Checking  the existence of entities with selected ids in loaded data.
         *
         * @param values
         *
         * @returns {boolean}
         */
        valueIsLoaded: function(values) {
            if (values) {
                let foundItems = 0;
                const self = this;
                _.each(values, function(item) {
                    if (self.selectedData && self.selectedData[item]) {
                        foundItems++;
                    }
                });

                return foundItems === values.length;
            }

            return true;
        },

        /**
         * Checking initialize select2 widget
         * hide criteria witout applying and validation value if select2 have not been initialize yet
         * @returns {*|void}
         * @private
         */
        _applyValueAndHideCriteria: function() {
            if (!this.isInitSelect2) {
                return this._hideCriteria();
            }

            DictionaryFilter.__super__._applyValueAndHideCriteria.call(this);
        },

        /**
         * @return {jQuery}
         */
        getCriteriaValueFieldToFocus() {
            const $el = DictionaryFilter.__super__.getCriteriaValueFieldToFocus.call(this);

            if ($el.data('select2')) {
                return $el.data('select2').search;
            }

            return $el;
        }
    });

    return DictionaryFilter;
});
