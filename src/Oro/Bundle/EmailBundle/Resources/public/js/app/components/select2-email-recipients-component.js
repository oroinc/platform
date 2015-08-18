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
            var emptyData = {id: '', text: ''};
            var searchChoice = {data: emptyData};

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

                self.$el.one('change', function(e) {
                    searchChoice.data = _.clone(emptyData);
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

            config.createSearchChoice = function(term, data) {
                if (!dataHasText(data, term)) {
                    searchChoice.data = _.clone(emptyData);
                    searchChoice.data.id = term;
                    searchChoice.data.text = term;

                    return searchChoice.data;
                }
            };

            this.$el.on('select2-init', function() {
                var select2 = $(this).data('select2');

                select2.search.on('keyup', function() {
                    var val = $(this).val();
                    if (!val) {
                        return;
                    }

                    searchChoice.data.text = val;
                    searchChoice.data.id = val;
                });

                var originalSelectHighlighted = select2.selectHighlighted;
                select2.selectHighlighted = function() {
                    if (!this.results.find('.select2-highlighted').length) {
                        var val = this.search.val();
                        this.onSelect({
                            id: val,
                            text: val
                        });
                    }

                    originalSelectHighlighted.apply(this, arguments);
                };
            });

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

    function dataHasText(data, text) {
        return _.some(data, function(row) {
            if (!row.hasOwnProperty('children')) {
                return row.text.localeCompare(text) === 0;
            }

            return dataHasText(row.children, text);
        });
    }

    return Select2EmailRecipientsComponent;
});
