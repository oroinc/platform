define(function (require) {
    'use strict';

    var EmailTreadView,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        routing = require('routing'),
        EmailItemView = require('./email-item-view'),
        BaseView = require('oroui/js/app/views/base/view');

    EmailTreadView = BaseView.extend({
        autoRender: true,

        events: {
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
            this.listenTo(mediator, 'widget:doRefresh:email-thread', function () {
                if (options.isBaseView) {
                    mediator.trigger('widget:doRefresh:email-thread-context');
                }
            });
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
                    .empty()
                    .append('<a href="#" class="email-view-toggle-all"></a>')
                    .find(this.selectors.toggleAll)
                    .on('click', _.bind(this.onToggleAllClick, this));
                this.updateToggleAllAction();
            }
            EmailTreadView.__super__.render.apply(this, arguments);
            this.initEmailItemViews(this.$(this.selectors.emailItem));
            return this;
        },

        /**
         * Handles click on toggle all action element
         *
         * @param {jQuery.Event} e
         */
        onToggleAllClick: function (e) {
            this.loadEmails().done(_.bind(this.toggleAllEmails, this));
        },

        /**
         * Expands or collapses all emails
         */
        toggleAllEmails: function () {
            var show = this._hasHiddenEmails();
            _.each(this.subviews, function (emailItemView) {
                emailItemView.toggle(show);
            });
        },

        /**
         * Handles click on load more email action element
         *
         * @param {jQuery.Event} e
         */
        onLoadMoreClick: function (e) {
            if (this.$(this.selectors.loadMore).hasClass('process')) {
                return;
            }
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
            var $content = $(content);
            this.$(this.selectors.loadMore).replaceWith($content);
            this.initEmailItemViews($content.filter(this.selectors.emailItem));
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
         * Initializes EmailItemView for all passed elements
         *
         * @param {Array<jQuery.Element>} $elems
         */
        initEmailItemViews: function ($elems) {
            _.each($elems, this._initEmailItemView, this);
        },

        /**
         * Creates EmailItemView for the element and registers it as subview of the thread
         *
         * @param {HTMLElement} elem
         * @protected
         */
        _initEmailItemView: function (elem) {
            var emailItemView;
            emailItemView = new EmailItemView({
                autoRender: true,
                el: elem
            });
            this.subview('email:' + emailItemView.cid, emailItemView);
            this.listenTo(emailItemView, {
                'toggle': this.updateToggleAllAction,
                'commentCountChanged': this.onCommentCountChange
            });
        },

        /**
         * Invokes refresh method for all emails
         */
        refreshEmails: function () {
            _.each(this.subviews, function (emailItemView) {
                emailItemView.refresh();
            });
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
            $toggleAllAction.toggle(hasMultipleEmails);
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
            return hasCollapsedEmails || hasEmailsToLoad;
        },

        /**
         * Handles comments count change (added/removed)
         *
         * @param {number} diff
         */
        onCommentCountChange: function (diff) {
            this.trigger('commentCountChanged', diff);
        }
    });

    return EmailTreadView;
});
