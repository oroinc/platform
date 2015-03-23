define(function (require) {
    'use strict';

    var EmailTreadView,
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        BaseView = require('oroui/js/app/views/base/view');

    EmailTreadView = BaseView.extend({
        autoRender: true,

        events: {
            'click .email-view-toggle': 'onEmailHeadClick',
            'click .email-view-toggle-all': 'onToggleAllClick'
        },

        selectors: {
            emailItem: '.email-info'
        },

        /**
         * @type {string}
         */
        actionPanelSelector: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            _.extend(this, _.pick(options, ['actionPanelSelector']));
            EmailTreadView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function () {
            if (this.actionPanelSelector) {
                // add toggleAll action element
                this.$(this.actionPanelSelector).append('<a href="#" class="email-view-toggle-all"></a>');
                this.updateToggleAllAction();
            }
            return EmailTreadView.__super__.render.apply(this, arguments);
        },

        /**
         * Handles click on toggle all action element
         *  - expands or collapses all full email bodies
         *
         * @param {jQuery.Event} e
         */
        onToggleAllClick: function (e) {
            var $emails = this.$(this.selectors.emailItem).not(':last'),
                show = this._hasHiddenEmails();
            this.toggleEmail($emails, show);
        },

        /**
         * Handles click on email head
         *  - expands or collapses full email body
         *
         * @param {jQuery.Event} e
         */
        onEmailHeadClick: function (e) {
            var $email, $target,
                exclude = 'a, .dropdown';

            $target = this.$(e.target);
            // if the target is an action element, skip toggling the email
            if ($target.is(exclude) || $target.parents(exclude).length) {
                return;
            }

            $email = this.$(e.currentTarget).closest(this.selectors.emailItem);
            // if this is the last email, skip toggling
            if ($email.is(':last-child')) {
                return;
            }

            this.toggleEmail($email);
        },

        /**
         * Expands or collapses full email body
         *
         * @param {jQuery} $email element related to the email
         * @param {boolean=} flag expand or collapse flag (true to expand)
         */
        toggleEmail: function ($email, flag) {
            $email.toggleClass('in', flag);
            this.updateToggleAllAction();
        },

        /**
         * Update toggle all action element
         */
        updateToggleAllAction: function () {
            var hasMultipleEmails, hasHiddenEmails, $toggleAllAction, translationPrefix;

            hasMultipleEmails = this.$(this.selectors.emailItem).length > 1;
            hasHiddenEmails = this._hasHiddenEmails();
            translationPrefix = 'oro.email.thread.' + (hasHiddenEmails ? 'expand_all' : 'collapse_all');

            // update action element
            $toggleAllAction = this.$('.email-view-toggle-all');
            $toggleAllAction[hasMultipleEmails ? 'show' : 'hide']();
            $toggleAllAction.text(__(translationPrefix + '.label'));
            $toggleAllAction.attr('title', __(translationPrefix + '.tooltip'));
        },

        /**
         * Check if there emails to show or to load
         *
         * @returns {boolean}
         * @protected
         */
        _hasHiddenEmails: function () {
            return Boolean(this.$(this.selectors.emailItem).not('.in').length);
        }
    });

    return EmailTreadView;
});
