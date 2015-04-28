define(function (require) {
    'use strict';

    var EmailBodyView,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseView = require('oroui/js/app/views/base/view');

    EmailBodyView = BaseView.extend({
        autoRender: true,

        /**
         * @type {string}
         */
        bodyContent: null,

        /**
         * @type {Array.<string>}
         */
        styles: null,

        events: {
            'click .email-extra-body-toggle': 'onEmailExtraBodyToggle'
        },

        listen: {
            'layout:reposition mediator': '_updateHeight'
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            _.extend(this, _.pick(options, ['bodyContent', 'styles']));
            this.$frame = this.$el;
            this.$frame.on('emailShown', _.bind(this._updateHeight, this));
            this.setElement(this.$el.contents().find('html'));
            EmailBodyView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            if (this.$frame) {
                this.$frame.off();
            }
            delete this.$frame;
            EmailBodyView.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        render: function () {
            var $content = $('<div />').append(this.bodyContent);
            this.$('head').html($content.find('head')[0] || '');
            this.$('body').html($content.find('body')[0] || $content);
            this._injectStyles();
            this._markEmailExtraBody();
            this._updateHeight();
            return this;
        },

        /**
         * Add application styles to the iframe document
         *
         * @protected
         */
        _injectStyles: function () {
            var $head;
            if (!this.styles) {
                return;
            }
            $head = this.$('head');
            _.each(this.styles, function (src) {
                $('<link/>', {rel: 'stylesheet', href: src}).appendTo($head);
            });
        },

        /**
         * Updates height for iFrame element depending on the email content size
         *
         * @protected
         */
        _updateHeight: function () {
            var $frame = this.$frame,
                $el = this.$el;
            _.defer(function () {
                $frame.height(0);
                $frame.height($el[0].scrollHeight);
            });
        },

        /**
         * Marks email extra-body part and adds the toggler
         *
         * @protected
         */
        _markEmailExtraBody: function () {
            var $extraBodies = this.$('body>.quote, body>.gmail_extra')
                .not('.email-extra-body')
                .addClass('email-extra-body');
            $('<div class="email-extra-body-toggle"></div>').insertBefore($extraBodies);
        },


        /**
         * Handles click on email extra-body toggle button
         * @param {jQuery.Event} e
         */
        onEmailExtraBodyToggle: function (e) {
            this.$(e.currentTarget)
                .next()
                .toggleClass('in');
            this._updateHeight();
        }
    });

    return EmailBodyView;
});
