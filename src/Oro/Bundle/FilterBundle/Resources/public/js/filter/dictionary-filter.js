define([
    'jquery',
    'routing',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    './abstract-filter',
    './choice-filter'
], function($, routing, _, __, tools, AbstractFilter, ChoiceFilter) {
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
        renderMode: 'select2',
        /**
         * Filter selector template
         *
         * @property
         */
        templateSelector: '#dictionary-filter-template',

        /**
         * Template selector for date field parts
         *
         * @property
         */
        fieldTemplateSelector: '#select-field-template',

        /**
         * Select widget options
         *
         * @property
         */
        widgetOptions: {
            multiple: true,
            classes: ''
        },

        /**
         * Minimal width of dropdown
         *
         * @private
         */
        minimumDropdownWidth: 120,

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
            DictionaryFilter.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            var className = this.constructor.prototype;
            var self = this;
            $.ajax({
                url: routing.generate(
                    'oro_api_get_dictionary_value_count',
                    {dictionary: className.filterParams.class.replace(/\\/g, '_'), limit: -1}
                ),
                success: function(data) {
                    self.count = data;
                    if (data > 10) {
                        self.componentMode = 'select2autocomplate';
                    } else {
                        self.componentMode = 'select2';
                    }

                    self.renderSelect2();
                },
                error: function(jqXHR) {
                    //messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                    //if (errorCallback) {
                    //    errorCallback(jqXHR);
                    //}
                }
            });
        },

        loadSelectedValue: function() {
            var self = this;
            var className = this.constructor.prototype;

            $.ajax({
                url: routing.generate(
                    'oro_dictionary_value',
                    {
                        dictionary: className.filterParams.class.replace(/\\/g, '_')
                    }
                ),
                data: {
                    'keys': this.value.value
                },
                success: function(reposne) {
                    self.value.value = reposne.results;
                    self._writeDOMValue(self.value);
                    self.applySelect2();
                },
                error: function(jqXHR) {
                    //messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                    //if (errorCallback) {
                    //    errorCallback(jqXHR);
                    //}
                }
            });
        },

        renderSelect2: function() {
            this.loadSelectedValue();
        },

        applySelect2: function() {
            var self = this;
            var className = this.constructor.prototype;
            var parts = this.getParts();
            var tt = _.template($(this.templateSelector).html());
            this.$el.append(tt({
                parts: parts
            }));

            if (this.componentMode === 'select2autocomplate') {
                this.$el.find('.select-values-autocomplete').removeClass('hide');
                this.$el.find('.select-values-autocomplete').attr('multiple','multiple').select2({
                    multiple: true,
                    containerCssClass: 'dictionary-filter',
                    ajax: {
                        url: routing.generate(
                            'oro_dictionary_filter',
                            {
                                dictionary: className.filterParams.class.replace(/\\/g, '_')
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
                            // parse the results into the format expected by Select2.
                            // since we are using custom formatting functions we do not need to
                            // alter the remote JSON data
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    dropdownAutoWidth: true,
                    escapeMarkup: function(markup) { return markup; }, // let our custom formatter work
                    minimumInputLength: 1
                });

                var value1 = [];
                $.each(this.value.value, function(index, value) {
                    value1.push({
                        'id': value.id,
                        'text': value.text
                    });
                });
                this.$el.find('.select-values-autocomplete').select2('data',  value1);
            }

            if (this.componentMode === 'select2') {
                $.ajax({
                    url: routing.generate(
                        'oro_dictionary_filter',
                        {
                            dictionary: className.filterParams.class.replace(/\\/g, '_')
                        }
                    ),
                    data: {
                        'q': ''
                    },
                    success: function(reposne) {
                        self.$el.find('.select-values').removeClass('hide');
                        $.each(reposne.results, function(index, value) {
                            var html = '<option value="' + value.id + '">' + value.text + '</option>';
                            self.$el.find('.select-values').append(html);
                        });
                        self.$el.find('.select-values').attr('multiple','multiple').select2({
                            containerCssClass: 'dictionary-filter',
                            dropdownAutoWidth: true
                        }).on('change', function(e) {
                            self.applyValue();
                        });

                        var value1 = [];
                        $.each(self.value.value, function(index, value) {
                            value1.push({
                                'id': value.id,
                                'text': value.text
                            });
                        });

                        self.$el.find('.select-values').select2('data', value1);
                    },
                    error: function(jqXHR) {
                        //messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                        //if (errorCallback) {
                        //    errorCallback(jqXHR);
                        //}
                    }
                });

            }

            this._criteriaRenderd = true;
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

            if (this.componentMode === 'select2autocomplate') {
                value.value = this.$el.find('.select-values-autocomplete').select2('val');
            } else if (this.componentMode === 'select2') {
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
        }
    });

    return DictionaryFilter;
});
