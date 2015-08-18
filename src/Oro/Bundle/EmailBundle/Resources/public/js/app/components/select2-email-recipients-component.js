define(['jquery', 'underscore', 'oro/select2-component'], function($, _, Select2Component) {
    'use strict';

    var contexts = {};
    var organizations = {};
    var currentOrganization = null;

    function processData(data) {
        if (typeof data === 'undefined') {
            return;
        }

        var parsedItem = JSON.parse(data);
        if (parsedItem.contextText) {
            contexts[parsedItem.key] = {};
            contexts[parsedItem.key] = {
                id: JSON.stringify(parsedItem.contextValue),
                text: parsedItem.contextText,
            };
            if (parsedItem.organization) {
                organizations[parsedItem.key] = parsedItem.organization;
            }
        }
    }

    var Select2EmailRecipientsComponent = Select2Component.extend({
        $el: null,
        $contextEl: null,

        initialize: function(options) {
            this.$el = options._sourceElement;
            this.$contextEl = $('[data-ftid=oro_email_email_contexts]');
            this._initEditation();
            Select2EmailRecipientsComponent.__super__.initialize.apply(this, arguments);
        },

        preConfig: function() {
            var config = Select2EmailRecipientsComponent.__super__.preConfig.apply(this, arguments);

            var self = this;
            config.ajax.results = function(data) {
                var results = _.map(data.results, function(section) {
                    if (typeof section.children === 'undefined') {
                        processData(section.data);
                        self._onRecipientAdd.call(self, section.id);

                        return {
                            id: section.id,
                            text: section.text
                        };
                    }

                    return {
                        text: section.text,
                        children: _.map(section.children, function(item) {
                            processData(item.data);

                            return {
                                id: item.id,
                                text: item.text
                            };
                        })
                    };
                });

                self.$el.on('change', function(e) {
                    var data = self.$contextEl.select2('data');

                    if (e.added) {
                        self._onRecipientAdd.call(self, e.added.id);
                    }

                    if (e.removed) {
                        if (typeof contexts[e.removed.id] !== 'undefined') {
                            var newData = _.reject(data, function(item) {
                                return item.id === contexts[e.removed.id].id;
                            });
                            self.$contextEl.select2('data', newData);
                        }

                        if (_.isEmpty(self.$el.select2('val'))) {
                            currentOrganization = null;
                        }
                    }
                });

                return {results: results};
            };

            var originalData = config.ajax.data;
            config.ajax.data = function() {
                var params = originalData.apply(this, arguments);

                if (currentOrganization) {
                    params.organization = currentOrganization;
                }

                return params;
            };

            return config;
        },

        _onRecipientAdd: function(id) {
            if (typeof contexts[id] === 'undefined') {
                return;
            }

            var data = this.$contextEl.select2('data');
            currentOrganization = _.result(organizations, id, null);
            data.push(contexts[id]);
            this.$contextEl.select2('data', data);
        },

        _initEditation: function() {
            var $el = this.$el;
            this.$el.parent('.controls').on('click', '.select2-search-choice', function(e) {
                var $choice = $(this);
                var $searchField = $(this).parent('.select2-choices').find('input');

                var originalData = $el.select2('data');
                var selectedIndex = $choice.index();
                var removedItem = originalData[selectedIndex];
                var newData = _.reject(originalData, function(item, index) {
                    return index === selectedIndex;
                });

                $el.select2('data', newData);
                $searchField.click().val(removedItem.text).trigger('paste');
            });
        }
    });

    return Select2EmailRecipientsComponent;
});
