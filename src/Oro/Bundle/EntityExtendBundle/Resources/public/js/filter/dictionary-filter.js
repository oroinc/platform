define([
    'require',
    'jquery.select2'
], function(require) {
    'use strict';

    var DictionaryFilter;
    var $ = require('jquery');
    var routing = require('routing');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var ChoiceFilter = require('oro/filter/choice-filter');
    var messenger = require('oroui/js/messenger');

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/dictionary-filter
     * @class   oro.filter.DictionaryFilter
     * @extends oro.filter.ChoiceFilter
     */
    DictionaryFilter = ChoiceFilter.extend({
        /**
         * select2 will apply to element with this selector
         */
        elementSelector: '.select-values-autocomplete',

        wrapperTemplateSelector: '#dictionary-filter-template',
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
            type: 'input[type="hidden"]:last'
        },

        nullLink: '#',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (this.filterParams) {
                this.dictionaryClass = this.filterParams.class.replace(/\\/g, '_');
            } else {
                this.dictionaryClass = this.class.replace(/\\/g, '_');
            }

            DictionaryFilter.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            this.renderDeferred = $.Deferred();
            this._renderCriteria();
        },

        _renderCriteria: function() {
            var self = this;

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
                    self._writeDOMValue(self.value);
                    self.renderTemplate();

                    self.applySelect2();
                    self.renderDeferred.resolve();
                },
                error: function(jqXHR) {
                    messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                }
            });
        },

        renderTemplate: function() {
            var value = _.extend({}, this.emptyValue, this.value);
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this.choices)) {
                var foundChoice = _.find(this.choices, function(choice) {
                    return (choice.value === value.type);
                });
                selectedChoiceLabel = foundChoice.label;
            }
            var parts = this._getParts();

            this.$el.addClass('filter-item oro-drop');
            this.$el.append(this.template({
                parts: parts,
                nullLink: this.nullLink,
                isEmpty: false,
                showLabel: this.showLabel,
                criteriaHint: 'All',
                label: this.label,
                selectedChoiceLabel: selectedChoiceLabel,
                selectedChoice: value.type,
                choices: this.choices,
                name: this.name
            }));
        },

        applySelect2: function() {
            var self = this;
            var select2Config = this.getSelect2Config();
            var select2element = this.$el.find(this.elementSelector);
            var values = this.getDataForSelect2();

            select2element.removeClass('hide');
            select2element.attr('multiple', 'multiple');
            select2element.select2(select2Config);
            if (this.templateTheme) {
                select2element.on('change', function () {
                    self.applyValue();
                });
            }
            select2element.select2('data',  values);

            this._criteriaRenderd = true;
        },

        getSelect2Config: function() {
            var config =  {
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
                    results: function(data) {
                        return {
                            results: data.results
                        };
                    }
                },
                dropdownAutoWidth: true,
                escapeMarkup: function(markup) { return markup; }, // let our custom formatter work
                minimumInputLength: 0
            };

            if (this.templateTheme === '') {
                config.width = 'resolve';
            }

            return config;
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

        _getParts: function() {
            var value = _.extend({}, this.emptyValue, this.getValue());
            var dictionaryPartTemplate = this._getTemplate(this.fieldTemplateSelector);
            var parts = [];
            var selectedPartLabel = this._getSelectedChoiceLabel('choices', this.value);
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

        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.type, value.type);
        },

        _readDOMValue: function() {
            return {
                type: this._getInputValue(this.criteriaValueSelectors.type),
                value: this.$el.find('.select-values-autocomplete').select2('val')
            };
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
        }
    });

    return DictionaryFilter;
});
