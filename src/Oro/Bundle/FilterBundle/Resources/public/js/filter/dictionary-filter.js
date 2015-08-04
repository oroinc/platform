define([
    'jquery',
    'routing',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    './choice-filter',
    'oroui/js/messenger'
], function($, routing, _, __, tools, ChoiceFilter, messenger) {
    'use strict';

    var DictionaryFilter;

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/multiselect-filter
     * @class   oro.filter.MultiSelectFilter
     * @extends oro.filter.SelectFilter
     */
    DictionaryFilter = ChoiceFilter.extend({
        /**
          * List available modes for filter
         * @property
         */
        mode: {
            dropdown: 'select2',
            autocomplete: 'select2autocomplate'
        },

        /**
         * Filter selector template
         *
         * @property
         */
        templateSelector: '#dictionary-filter-template',

        /**
         * Template selector for dictionary field parts
         *
         * @property
         */
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
            type: 'select[name="dictionary_part"]',
            data: 'input.select2-focusser'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.dictionaryClass = this.filterParams.class.replace(/\\/g, '_');
            DictionaryFilter.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            var self = this;
            this.getCountDeferred = $.Deferred();
            $.ajax({
                url: routing.generate(
                    'oro_dictionary_count',
                    {dictionary: this.dictionaryClass, limit: -1}
                ),
                success: function(response) {
                    self.count = response.result;
                    self.getCountDeferred.resolve();
                },
                error: function(jqXHR) {
                    messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                }
            });

            this.getCountDeferred.done(_.bind(this.renderSelect2, this));
        },

        renderSelect2: function() {
            if (this.count > this.maxCountForDropDownMode) {
                this.setComponentMode(this.mode.autocomplete);
            } else {
                this.setComponentMode(this.mode.dropdown);
            }
            this.loadSelectedValue();
        },

        loadSelectedValue: function() {
            var self = this;
            this.selecteValueDeferred = $.Deferred();

            $.ajax({
                url: routing.generate(
                    'oro_dictionary_value',
                    {
                        dictionary: this.dictionaryClass
                    }
                ),
                data: {
                    'keys': this.value.value
                },
                success: function(reposne) {
                    self.value.value = reposne.results;
                    self.selecteValueDeferred.resolve();
                },
                error: function(jqXHR) {
                    messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                }
            });

            this.selecteValueDeferred.done(function() {
                self._writeDOMValue(self.value);
                self.renderTemplate();
                self.applySelect2();
            });
        },

        renderTemplate: function() {
            var parts = this.getParts();
            var template = _.template($(this.templateSelector).html());
            this.$el.append(template({
                parts: parts
            }));
        },

        applySelect2: function() {
            var self = this;
            var select2Config = this.getSelect2Config();

            var select2element = this.$el.find(this.getElementClass());
            if (this.componentMode === this.mode.autocomplete) {
                var values = this.getDataForSelect2();
                select2element.removeClass('hide')
                    .attr('multiple', 'multiple')
                    .select2(select2Config);
                select2element.select2('data',  values);
            }

            if (this.componentMode === this.mode.dropdown) {
                $.ajax({
                    url: routing.generate(
                        'oro_dictionary_search',
                        {
                            dictionary: this.dictionaryClass
                        }
                    ),
                    data: {
                        'q': ''
                    },
                    success: function(reposne) {
                        select2element.removeClass('hide');
                        $.each(reposne.results, function(index, value) {
                            var html = '<option value="' + value.id + '">' + value.text + '</option>';
                            select2element.append(html);
                        });
                        select2element.attr('multiple', 'multiple').select2(select2Config).on('change', function(e) {
                            self.applyValue();
                        });

                        var values = self.getDataForSelect2();
                        select2element.select2('data', values);
                    },
                    error: function(jqXHR) {
                        messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                    }
                });
            }

            this._criteriaRenderd = true;
        },

        getSelect2Config: function() {
            var config = {};

            if (this.componentMode === this.mode.autocomplete) {
                config = {
                    multiple: true,
                    containerCssClass: 'dictionary-filter',
                    ajax: {
                        url: routing.generate(
                            'oro_dictionary_search',
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
                        results: function(data, page) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    dropdownAutoWidth: true,
                    escapeMarkup: function(markup) { return markup; }, // let our custom formatter work
                    minimumInputLength: 1
                };
            } else {
                config = {
                    containerCssClass: 'dictionary-filter',
                    dropdownAutoWidth: true
                };
            }

            return config;
        },

        getElementClass: function() {
            if (this.componentMode === this.mode.autocomplete) {
                this.elementSelector = '.select-values-autocomplete';
            }

            if (this.componentMode === this.mode.dropdown) {
                this.elementSelector = '.select-values';
            }

            return this.elementSelector;
        },

        getDataForSelect2: function() {
            var values = [];
            $.each(this.value.value, function(index, value) {
                values.push({
                    'id': value.id,
                    'text': value.text
                });
            });

            return values;
        },

        isEmptyValue: function() {
            return false;
        },

        getParts: function() {
            var value = _.extend({}, this.emptyValue, this.getValue());
            var datePartTemplate = this._getTemplate(this.fieldTemplateSelector);
            var parts = [];
            var selectedPartLabel = this._getSelectedChoiceLabel('choices', this.value);
            // add date parts only if embed template used
            if (this.templateTheme !== '') {
                parts.push(
                    datePartTemplate({
                        name: this.name + '_part',
                        choices: this.choices,
                        selectedChoice: value.type,
                        selectedChoiceLabel: selectedPartLabel
                    })
                );
            }

            return parts;
        },

        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.type, value.type);
            this._setInputValue(this.criteriaValueSelectors.data, value.data);

            return this;
        },

        _readDOMValue: function() {
            var value = {};
            value.type = this._getInputValue(this.criteriaValueSelectors.type);

            if (this.componentMode === this.mode.autocomplete) {
                value.value = this.$el.find('.select-values-autocomplete').select2('val');
            } else if (this.componentMode === this.mode.dropdown) {
                value.value = this.$el.find('.select-values').select2('val');
            } else {
                value.value = this.value.value;
            }

            return value;
        },

        _getSelectedChoiceLabel: function(property, value) {
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                var foundChoice = _.find(this[property], function(choice) {
                    return (choice.value === value.type);
                });
                selectedChoiceLabel = foundChoice.label;
            }

            return selectedChoiceLabel;
        },

        setComponentMode: function(mode) {
            this.componentMode = mode;

            return this;
        }
    });

    return DictionaryFilter;
});
