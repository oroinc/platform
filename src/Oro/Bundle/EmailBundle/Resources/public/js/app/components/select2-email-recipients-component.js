define(['jquery', 'underscore', 'oro/select2-component'], function($, _, Select2Component) {
    'use strict';

    var Select2EmailRecipientsComponent = Select2Component.extend({
        $el: null,
        $contextEl: null,

        initialize: function(options) {
            this.$el = options._sourceElement;
            this.$contextEl = $('[data-ftid=oro_email_email_contexts]');
            Select2EmailRecipientsComponent.__super__.initialize.apply(this, arguments);
        },

        preConfig: function() {
            var config = Select2EmailRecipientsComponent.__super__.preConfig.apply(this, arguments);

            var contexts = {};
            var self = this;
            config.ajax.results = function(data) {
                var results = _.map(data.results, function(section) {
                    if (typeof section.children === 'undefined') {
                        return section;
                    }

                    return {
                        text: section.text,
                        children: _.map(section.children, function(item) {
                            var parsedItem = JSON.parse(item.id);
                            if (parsedItem.contextText) {
                                contexts[parsedItem.key] = {};
                                contexts[parsedItem.key] = {
                                    id: JSON.stringify(parsedItem.contextValue),
                                    text: parsedItem.contextText,
                                };
                            }

                            return {
                                id: parsedItem.key,
                                text: item.text
                            };
                        })
                    };
                });

                self.$el.on('change', function(e) {
                    var data = self.$contextEl.select2('data');

                    if (e.added && typeof contexts[e.added.id] !== 'undefined') {
                        data.push(contexts[e.added.id]);
                        self.$contextEl.select2('data', data);
                    }

                    if (e.removed && typeof contexts[e.removed.id] !== 'undefined') {
                        var newData = _.reject(data, function(item) {
                            return item.id === contexts[e.removed.id].id;
                        });
                        self.$contextEl.select2('data', newData);
                    }
                });

                return {results: results};
            };

            return config;
        }
    });

    return Select2EmailRecipientsComponent;
});
