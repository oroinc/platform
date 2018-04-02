define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroform/js/app/views/select2-view');

    var Select2EmailRecipientsView = BaseView.extend({
        $contextEl: null,

        clearSearch: null,

        events: {
            'change': '_onchange',
            'recipient:add': '_onRecipientAdd',
            'select2-blur': '_onSelect2Blur'
        },

        /**
         * @inheritDoc
         */
        constructor: function Select2EmailRecipientsView() {
            Select2EmailRecipientsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.$contextEl = $('[data-ftid=oro_email_email_contexts]');
            this.$el.on('input-widget:init', _.bind(this._onSelect2Init, this));
            this._initEditation();
            Select2EmailRecipientsView.__super__.initialize.apply(this, arguments);
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

            var data = this.$contextEl.inputWidget('data');
            this.$el.data('organization', _.result(organizations, id, null));
            data.push(contexts[id]);
            this.$contextEl.inputWidget('data', data);
        },

        _onchange: function(e) {
            this.$el.valid();
            this.searchChoice.data = {id: '', text: ''};

            var data = this.$contextEl.inputWidget('data');
            if (e.added) {
                this.$el.trigger('recipient:add', e.added.id);
            }

            if (e.removed) {
                var contexts = this.$el.data('contexts');
                if (typeof contexts[e.removed.id] !== 'undefined') {
                    var newData = _.reject(data, function(item) {
                        return item.id === contexts[e.removed.id].id;
                    });
                    this.$contextEl.inputWidget('data', newData);
                }

                if (_.isEmpty(this.$el.inputWidget('data'))) {
                    this.$el.data('organization', null);
                }
            } else {
                this.clearSearch();
            }
        },

        _onSelect2Init: function() {
            var select2 = this.$el.data('select2');
            this.select2 = select2;
            this.searchChoice = this.$el.data('search-choice');

            /**
             * Updates searchChoice value so that if user press enter
             * it will select currently typed text
             * (not custom selected one in case last response wasn't finished or even started)
             */
            select2.search.on('keyup', _.bind(this._onKeyUp, this));
            select2.search.on('paste', _.bind(this._onPaste, this));

            /**
             * It will select currently typed value if enter is pressed
             * in case no response from server was ever received yet for current autocomplete
             */
            select2.selectHighlighted = _.wrap(select2.selectHighlighted, _.bind(this._selectHighlighted, this));
            this.clearSearch = _.bind(select2.clearSearch, select2);
            select2.clearSearch = function() {};
            this.clearSearch();
        },

        _selectHighlighted: function(originalMethod) {
            var val = this.select2.search.val();
            if (val) {
                var valueExistsAlready = _.some(this.select2.opts.element.inputWidget('data'), function(item) {
                    return val === item.text;
                });

                if (!valueExistsAlready) {
                    this.select2.onSelect({
                        id: this._generateId(val),
                        text: val
                    });
                } else {
                    return false;
                }
            }
            return originalMethod.apply(this.select2, _.rest(arguments));
        },

        _onKeyUp: function() {
            var val = this._extractItemsFromSearch(true);
            if (!val) {
                return;
            }
            this.searchChoice.data.text = val;
            this.searchChoice.data.id = this._generateId(val);
        },

        _onPaste: function() {
            this.select2.search.one('input', _.bind(function(e) {
                this._extractItemsFromString(e.currentTarget.value);
            }, this));
        },

        _onSelect2Blur: function() {
            if (!this.select2.opened()) {
                this._extractItemsFromSearch();
            }
            this.clearSearch();
        },

        /**
         * Make selected data editable
         */
        _initEditation: function() {
            var $el = this.$el;
            $el.parent('.controls').on('click', '.select2-search-choice', _.bind(function(e) {
                var $choice = $(e.currentTarget);
                this._extractItemsFromSearch();
                $el.one('change', _.bind(function(e) {
                    $el.inputWidget('search', e.removed.text);
                }, this));
                $choice.find('.select2-search-choice-close').click();
            }, this));
        },

        _extractItemsFromSearch: function(withoutLast) {
            var value = $(this.select2.search).val() || '';
            var rest = this._extractItemsFromString(value, withoutLast);
            if (rest && rest !== value) {
                this.select2.externalSearch(rest);
            }
        },

        _extractItemsFromString: function(value, withoutLast) {
            var gate = withoutLast ? 2 : 1;
            var rest = '';
            var splitRegEx = new RegExp('[' + Select2EmailRecipientsView.SEPARATORS.join() + ']');
            var parts = value.split(splitRegEx);
            if (parts.length >= gate) {
                if (withoutLast) {
                    rest = parts.pop();
                }
                var existingValues = _.pluck(this.select2.data(), 'text');
                parts.forEach(_.bind(function(item) {
                    item = item.trim();
                    if (item.length > 0) {
                        if (!_.contains(existingValues, item)) {
                            this.select2.onSelect({
                                id: this._generateId(item),
                                text: item
                            }, {noFocus: !withoutLast});
                        }
                    }
                }, this));
            } else {
                rest = value;
            }
            return rest;
        },

        _generateId: function(value) {
            return value;
        }
    }, {
        SEPARATORS: [',', ';']
    });

    return Select2EmailRecipientsView;
});
