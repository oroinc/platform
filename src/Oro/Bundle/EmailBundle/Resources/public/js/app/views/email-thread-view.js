define(function (require) {
    'use strict';

    var EmailTreadView,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        routing = require('routing'),
        BaseView = require('oroui/js/app/views/base/view');

    EmailTreadView = BaseView.extend({
        autoRender: true,

        events: {
            'click .email-view-toggle': 'onEmailHeadClick',
            'click .email-view-toggle-all': 'onToggleAllClick',
            'click .email-load-more': 'onLoadMoreClick'
        },

        selectors: {
            emailItem: '.email-info',
            loadMore: '.email-load-more',
            toggleAll: '.email-view-toggle-all'
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
        dispose: function () {
            if (this.$actionPanel) {
                this.$actionPanel.find(this.selectors.toggleAll).remove();
                delete this.$actionPanel;
            }
            EmailTreadView.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function () {
            if (this.actionPanelSelector) {
                // add toggleAll action element
                this.$actionPanel = $(this.actionPanelSelector);
                this.$actionPanel
                    .append('<a href="#" class="email-view-toggle-all"></a>')
                    .find(this.selectors.toggleAll)
                    .on('click', _.bind(this.onToggleAllClick, this));
                this.updateToggleAllAction();
            }
            EmailTreadView.__super__.render.apply(this, arguments);
            mediator.execute('layout:init', this.$el, this);
            return this;
        },

        /**
         * Handles click on toggle all action element
         *  - expands or collapses all full email bodies
         *
         * @param {jQuery.Event} e
         */
        onToggleAllClick: function (e) {
            this.loadEmails().done(_.bind(function () {
                var $emails = this.$(this.selectors.emailItem).not(':last'),
                    show = this._hasHiddenEmails();
                this.toggleEmail($emails, show);
            }, this));
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
         * Handles click on load more email action element
         *
         * @param {jQuery.Event} e
         */
        onLoadMoreClick: function (e) {
            this.loadEmails();
        },

        /**
         * Loads emails' html
         *
         * @returns {Promise}
         */
        loadEmails: function () {
            var url, ids, promise;
            ids = this.$(this.selectors.loadMore).addClass('process').data('emailsItems');
            if (ids) {
                url = routing.generate('oro_email_items_view', {ids: ids.join(',')});
                promise = $.ajax(url)
                    .done(_.bind(this.onDoneLoadEmails, this))
                    .fail(_.bind(this.onFailLoadEmails, this));
            } else {
                promise = $.Deferred().resolve('').promise();
            }
            return promise;
        },

        /**
         * Handles emails load and update email thread
         *
         * @param {string} content
         */
        onDoneLoadEmails: function (content) {
            if (this.disposed) {
                return;
            }
            this.$(this.selectors.loadMore).replaceWith(content);
            mediator.execute('layout:init', this.$el, this);
        },

        /**
         * Handles emails loading error
         */
        onFailLoadEmails: function () {
            if (this.disposed) {
                return;
            }
            this.$(this.selectors.loadMore).removeClass('process');
            mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
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
            $toggleAllAction = this.$actionPanel.find(this.selectors.toggleAll);
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
            var hasCollapsedEmails, hasEmailsToLoad;
            hasCollapsedEmails = Boolean(this.$(this.selectors.emailItem).not('.in').length);
            hasEmailsToLoad = Boolean(this.$(this.selectors.loadMore).length);
            return hasCollapsedEmails|| hasEmailsToLoad;
        }
    });

    return EmailTreadView;
});
