define([
    'jquery',
    'underscore',
    'oro/select2-component',
    'oroemail/js/app/views/select2-email-recipients-view'
], function($, _, Select2Component, Select2View) {
    'use strict';

    function dataHasText(data, text) {
        return _.some(data, function(row) {
            if (!row.hasOwnProperty('children')) {
                return row.text.localeCompare(text) === 0;
            }

            return dataHasText(row.children, text);
        });
    }

    var Select2EmailRecipientsComponent = Select2Component.extend({
        ViewType: Select2View,

        $el: null,

        initialize: function(options) {
            this.$el = options._sourceElement;
            Select2EmailRecipientsComponent.__super__.initialize.apply(this, arguments);
        },

        preConfig: function() {
            var config = Select2EmailRecipientsComponent.__super__.preConfig.apply(this, arguments);
            var searchChoice = {data: {id: '', text: ''}};
            this.$el.data({
                'search-choice': searchChoice,
                'organizations': [],
                'organization': null,
                'contexts': []
            });

            var self = this;
            config.ajax.results = function(data) {
                return {results: self._processResultData.call(self, data)};
            };

            /**
             * Adds organization in request parameters so
             * there is no mix of organizations in records
             */
            var originalData = config.ajax.data;
            config.ajax.data = function() {
                var params = originalData.apply(this, arguments);

                if (self.$el.data('organization')) {
                    params.organization = self.$el.data('organization');
                }

                return params;
            };

            /**
             * Creates choice which can be modified later to prevent selection of
             * previously autocompleted text instead of text currently typed
             */
            config.createSearchChoice = function(term, data) {
                var selectedData = this.opts.element.inputWidget('valData');
                if (!dataHasText(data, term) && !dataHasText(selectedData, term)) {
                    searchChoice.data = {id: '', text: ''};
                    searchChoice.data.id = term;
                    searchChoice.data.text = term;

                    return searchChoice.data;
                }
            };

            return config;
        },

        /**
         * Extracts metadata, update contexts, retrieve data for select2
         */
        _processResultData: function(data) {
            var self = this;
            return _.map(data.results, function(section) {
                if (typeof section.children === 'undefined') {
                    self._processData(section.data);
                    self.$el.trigger('recipient:add', section.id);

                    return {
                        id: section.id,
                        text: section.text
                    };
                }

                return {
                    text: section.text,
                    children: _.map(section.children, function(item) {
                        self._processData(item.data);

                        return {
                            id: item.id,
                            text: item.text
                        };
                    })
                };
            });
        },

        /**
        * Extracts contexts and organizations from data
        */
        _processData: function(data) {
           if (typeof data === 'undefined' || this.disposed) {
               return;
           }

           var contexts = this.$el.data('contexts');
           var organizations = this.$el.data('organizations');

           var parsedItem = JSON.parse(data);
           if (parsedItem.contextText) {
               contexts[parsedItem.key] = {};
               contexts[parsedItem.key] = {
                   id: JSON.stringify(parsedItem.contextValue),
                   text: parsedItem.contextText
               };
               if (parsedItem.organization) {
                   organizations[parsedItem.key] = parsedItem.organization;
               }
           }
       }
    });

    return Select2EmailRecipientsComponent;
});
