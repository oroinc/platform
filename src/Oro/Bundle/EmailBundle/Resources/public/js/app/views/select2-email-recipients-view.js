define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Select2View = require('oroform/js/app/views/select2-view');

    const Select2EmailRecipientsView = Select2View.extend({
        $contextEl: null,

        clearSearch: null,

        events: {
            'change': '_onchange',
            'recipient:add': '_onRecipientAdd',
            'select2-blur': '_onSelect2Blur'
        },

        /**
         * @inheritdoc
         */
        constructor: function Select2EmailRecipientsView(options) {
            Select2EmailRecipientsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$contextEl = $('[data-ftid=oro_email_email_contexts]');
            this.$el.on('input-widget:init', this._onSelect2Init.bind(this));
            this._initEditation();
            Select2EmailRecipientsView.__super__.initialize.call(this, options);
        },

        /**
         * Adds selected recipient in context
         * + mark organization of the recipient as current one
         */
        _onRecipientAdd: function(e, id) {
            const contexts = this.$el.data('contexts');
            const organizations = this.$el.data('organizations');
            if (typeof contexts[id] === 'undefined') {
                return;
            }

            const data = this.$contextEl.inputWidget('data');
            this.$el.data('organization', _.result(organizations, id, null));
            data.push(contexts[id]);
            this.$contextEl.inputWidget('data', data);
        },

        _onchange: function(e) {
            this.$el.valid();
            this.searchChoice.data = {id: '', text: ''};

            const data = this.$contextEl.inputWidget('data');
            if (e.added) {
                this.$el.trigger('recipient:add', e.added.id);
            }

            if (e.removed) {
                const contexts = this.$el.data('contexts');
                if (typeof contexts[e.removed.id] !== 'undefined') {
                    const newData = _.reject(data, function(item) {
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
            const select2 = this.$el.data('select2');
            this.select2 = select2;
            this.searchChoice = this.$el.data('search-choice');

            /**
             * Updates searchChoice value so that if user press enter
             * it will select currently typed text
             * (not custom selected one in case last response wasn't finished or even started)
             */
            select2.search.on('keyup', this._onKeyUp.bind(this));
            select2.search.on('paste', this._onPaste.bind(this));

            /**
             * It will select currently typed value if enter is pressed
             * in case no response from server was ever received yet for current autocomplete
             */
            select2.selectHighlighted = _.wrap(select2.selectHighlighted, this._selectHighlighted.bind(this));
            this.clearSearch = select2.clearSearch.bind(select2);
            select2.clearSearch = function() {};
            this.clearSearch();
        },

        _selectHighlighted: function(originalMethod, ...rest) {
            const val = this.select2.search.val();
            if (val) {
                const valueExistsAlready = _.some(this.select2.opts.element.inputWidget('data'), function(item) {
                    return val === item.text;
                });

                if (!valueExistsAlready) {
                    const $highlighted = this.select2.results.find('.select2-highlighted');
                    let data = $highlighted.closest('.select2-result').data('select2-data');

                    if (data === void 0) {
                        data = {
                            id: this._generateId(val),
                            text: val
                        };
                    }

                    this.select2.onSelect(data);
                } else {
                    return false;
                }
            }
            return originalMethod.apply(this.select2, rest);
        },

        _onKeyUp: function() {
            const val = this._extractItemsFromSearch(true);
            if (!val) {
                return;
            }
            this.searchChoice.data.text = val;
            this.searchChoice.data.id = this._generateId(val);
        },

        _onPaste: function() {
            this.select2.search.one('input', e => {
                this._extractItemsFromString(e.currentTarget.value);
            });
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
            const $el = this.$el;
            $el.parent('.controls').on('click', '.select2-search-choice', e => {
                const $choice = $(e.currentTarget);
                this._extractItemsFromSearch();
                $el.one('change', e => {
                    $el.inputWidget('search', e.removed.text);
                });
                $choice.find('.select2-search-choice-close').click();
            });
        },

        _extractItemsFromSearch: function(withoutLast) {
            const value = $(this.select2.search).val() || '';
            const rest = this._extractItemsFromString(value, withoutLast);
            if (rest && rest !== value) {
                this.select2.externalSearch(rest);
            }
        },

        _extractItemsFromString: function(value, withoutLast) {
            const gate = withoutLast ? 2 : 1;
            let rest = '';
            const splitRegEx = new RegExp('[' + Select2EmailRecipientsView.SEPARATORS.join() + ']');
            const parts = value.split(splitRegEx);
            if (parts.length >= gate) {
                if (withoutLast) {
                    rest = parts.pop();
                }
                const existingValues = _.pluck(this.select2.data(), 'text');
                parts.forEach(item => {
                    item = item.trim();
                    if (item.length > 0) {
                        if (!_.contains(existingValues, item)) {
                            this.select2.onSelect({
                                id: this._generateId(item),
                                text: item
                            }, {noFocus: !withoutLast});
                        }
                    }
                });
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
