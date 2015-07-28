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
        renderMode: 'select2',
        /**
         * Filter selector template
         *
         * @property
         */
        templateSelector: '#dictionary-filter-template',

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
            console.log(5, 'initialization filter');
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
                    console.log(data);
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
        renderSelect2: function() {
            debugger;
            var className = this.constructor.prototype;
            var tt = _.template($(this.templateSelector).html());
            this.$el.append(tt);

            if (this.componentMode === 'select2autocomplate') {
                this.$el.find('.select-values-autocomplete').removeClass('hide');
                this.$el.find('.select-values-autocomplete').attr('multiple','multiple').select2({
                    multiple: true,
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
                        data: function (params) {
                            console.log('params', params);
                            return {
                                q: params // search term
                            };
                        },
                        results: function (data, page) {
                            // parse the results into the format expected by Select2.
                            // since we are using custom formatting functions we do not need to
                            // alter the remote JSON data
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    dropdownAutoWidth : true,
                    escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                    minimumInputLength: 1
                });
            }

            if (this.componentMode === 'select2') {
                var self = this;
                var proto = this.__proto__;
                console.log(proto);

                this.$el.find('.select-values').removeClass('hide');
                $.each(proto.choices, function(index, value) {
                    self.$el.find('.select-values').append('<option value="'+ value.value +'">'+value.label+ '</option>');
                });
                //this.$el.find('.select-values')
                this.$el.find('.select-values').attr('multiple','multiple').select2({
                    //multiple: true
                    dropdownAutoWidth : true
                }).on('change', function (e) {
                    console.log('this.value', this.value);
                    self.applyValue();
                });
            }
        },
        isEmptyValue: function() {
            return false;
        },
        getValue:function() {
            var value;
            if (this.componentMode === 'select2autocomplate') {
                value =  this.$el.find('.select-values-autocomplete').select2("val");
            }

            if (this.componentMode === 'select2') {
                value =  this.$el.find('.select-values').select2("val");
            }
            return value;
        },
        _writeDOMValue: function(value) {
            this._setInputValue(this.inputSelector, value);
            return this;
        },
        _readDOMValue: function() {
            return this.getValue();
        }
    });

    return DictionaryFilter;
});
