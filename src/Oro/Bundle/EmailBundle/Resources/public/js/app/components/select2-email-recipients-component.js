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

    const Select2EmailRecipientsComponent = Select2Component.extend({
        ViewType: Select2View,

        $el: null,

        /**
         * @inheritdoc
         */
        constructor: function Select2EmailRecipientsComponent(options) {
            Select2EmailRecipientsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            Select2EmailRecipientsComponent.__super__.initialize.call(this, options);
        },

        preConfig: function(config) {
            Select2EmailRecipientsComponent.__super__.preConfig.call(this, config);
            const searchChoice = {data: {id: '', text: ''}};
            this.$el.data({
                'search-choice': searchChoice,
                'organizations': [],
                'organization': null,
                'contexts': []
            });

            const self = this;
            config.ajax.results = function(data) {
                return {results: self._processResultData(data)};
            };

            /**
             * Adds organization in request parameters so
             * there is no mix of organizations in records
             */
            const originalData = config.ajax.data;
            config.ajax.data = function(...args) {
                const params = originalData.apply(this, args);

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
                const selectedData = this.opts.element.inputWidget('data');
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
            const self = this;
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

            const contexts = this.$el.data('contexts');
            const organizations = this.$el.data('organizations');

            const parsedItem = JSON.parse(data);
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
