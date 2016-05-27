define([
    'jquery',
    'underscore',
    'oroform/js/app/views/select2-view'
], function($, _, BaseView) {
    'use strict';

    var Select2View = BaseView.extend({
        $contextEl: null,
        clearSearch: null,

        initialize: function() {
            this.$contextEl = $('[data-ftid=oro_email_email_contexts]');
            this.$el.on('input-widget:init', _.bind(this._onSelect2Init, this));
            this.$el.on('recipient:add', _.bind(this._onRecipientAdd, this));
            this.$el.on('change', _.bind(this._onchange, this));
            this._initEditation();
            Select2View.__super__.initialize.apply(this, arguments);
        },

        /**
         * Adds selected recipient in context
         * + mark organization of the recipient as current one
         */
        _onRecipientAdd: function(e, id) {
            var contexts = this.$el.data('contexts');
            var organizations = this.$el.data('organizations');
            if (typeof contexts[id] === 'undefined') {
                return;
            }

            var data = this.$contextEl.inputWidget('valData');
            this.$el.data('organization', _.result(organizations, id, null));
            data.push(contexts[id]);
            this.$contextEl.inputWidget('valData', data);
        },

        _onchange: function(e) {
            this.$el.valid();
            var searchChoice = this.$el.data('search-choice');
            searchChoice.data = {id: '', text: ''};

            var data = this.$contextEl.inputWidget('valData');
            if (e.added) {
                this.$el.trigger('recipient:add', e.added.id);
            }

            if (e.removed) {
                var contexts = this.$el.data('contexts');
                if (typeof contexts[e.removed.id] !== 'undefined') {
                    var newData = _.reject(data, function(item) {
                        return item.id === contexts[e.removed.id].id;
                    });
                    this.$contextEl.inputWidget('valData', newData);
                }

                if (_.isEmpty(this.$el.inputWidget('valData'))) {
                    this.$el.data('organization', null);
                }
            }
        },

        _onSelect2Init: function() {
            var select2 = this.$el.data('select2');
            var searchChoice = this.$el.data('search-choice');

            this.clearSearch = _.bind(select2.clearSearch, select2);
            select2.clearSearch = function() {
            };
            this.clearSearch();
            this.$el.on('change select2-blur', this.clearSearch);

            /**
             * Updates searchChoice value so that if user press enter
             * it will select currently typed text
             * (not custom selected one in case last response wasn't finished or even started)
             */
            select2.search.on('keyup', function() {
                var val = $(this).val();
                if (!val) {
                    return;
                }

                searchChoice.data.text = val;
                searchChoice.data.id = val;
            });

            /**
             * It will select currently typed value if enter is pressed
             * in case no response from server was ever received yet for current autocomplete
             */
            var originalSelectHighlighted = select2.selectHighlighted;
            select2.selectHighlighted = function() {
                if (!this.results.find('.select2-highlighted').length) {
                    var val = this.search.val();
                    if (val) {
                        var valueExistsAlready = _.some(this.opts.element.inputWidget('valData'), function(item) {
                            return val === item.id;
                        });

                        if (!valueExistsAlready) {
                            this.onSelect({
                                id: val,
                                text: val
                            });
                        }
                    }
                }

                originalSelectHighlighted.apply(this, arguments);
            };
        },

        /**
         * Make selected data editable
         */
        _initEditation: function() {
            var $el = this.$el;
            $el.parent('.controls').on('click', '.select2-search-choice', function() {
                var $choice = $(this);

                $el.one('change', function(e) {
                    $el.inputWidget('search', e.removed.text);
                });
                $choice.find('.select2-search-choice-close').click();
            });
        }
    });

    return Select2View;
});
