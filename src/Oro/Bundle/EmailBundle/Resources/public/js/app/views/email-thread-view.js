define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const tools = require('oroui/js/tools');
    const EmailItemView = require('./email-item-view');
    const BaseView = require('oroui/js/app/views/base/view');

    const EmailTreadView = BaseView.extend({
        autoRender: true,

        events: {
            'click .email-view-toggle-all': 'onToggleAllClick',
            'click [data-role="email-load-more"]': 'onLoadMoreClick',
            'shown.bs.dropdown .email-detailed-info-table.dropdown': 'onDetailedInfoOpen'
        },

        selectors: {
            emailItem: '.email-info',
            loadMore: '[data-role="email-load-more"]',
            toggleAll: '.email-view-toggle-all'
        },

        /**
         * @type {string}
         */
        actionPanelSelector: null,

        /**
         * @inheritdoc
         */
        constructor: function EmailTreadView(options) {
            EmailTreadView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['actionPanelSelector']));
            EmailTreadView.__super__.initialize.call(this, options);
            this.listenTo(mediator, 'widget:doRefresh:email-thread', function() {
                if (options.isBaseView) {
                    mediator.trigger('widget:doRefresh:email-thread-context');
                }
            });
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.$actionPanel) {
                this.$actionPanel.find(this.selectors.toggleAll).remove();
                delete this.$actionPanel;
            }
            EmailTreadView.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            if (this.actionPanelSelector) {
                // add toggleAll action element
                this.$actionPanel = $(this.actionPanelSelector);
                this.$actionPanel
                    .empty()
                    .append('<a href="#" class="email-view-toggle-all"></a>')
                    .find(this.selectors.toggleAll)
                    .on('click', this.onToggleAllClick.bind(this));
                this.updateToggleAllAction();
            }
            EmailTreadView.__super__.render.call(this);

            this._deferredRender();
            this.initEmailItemViews(this.$(this.selectors.emailItem))
                .then(this._resolveDeferredRender.bind(this));

            return this;
        },

        /**
         * Handles click on toggle all action element
         *
         * @param {jQuery.Event} e
         */
        onToggleAllClick: function(e) {
            this.loadEmails().done(this.toggleAllEmails.bind(this));
        },

        /**
         * Expands or collapses all emails
         */
        toggleAllEmails: function() {
            const show = this._hasHiddenEmails();
            _.each(this.subviews, function(emailItemView) {
                emailItemView.toggle(show);
            });
        },

        /**
         * Handles click on load more email action element
         *
         * @param {jQuery.Event} e
         */
        onLoadMoreClick: function(e) {
            if (this.$(this.selectors.loadMore).hasClass('process')) {
                return;
            }
            this.loadEmails();
        },

        onDetailedInfoOpen: function(e) {
            const $target = $('>.dropdown-menu', e.currentTarget);
            const target = $target[0];
            const parentRect = this.el.getBoundingClientRect();
            $target.removeAttr('data-uid').removeClass('fixed-width').css({
                width: '',
                left: ''
            });
            const limitWidth = Math.min(target.clientWidth, parentRect.width);
            if (target.scrollWidth > limitWidth) {
                $target.outerWidth(limitWidth).addClass('fixed-width');
            }
            const rect = target.getBoundingClientRect();
            const left = parseInt($target.css('left'));
            const uid = 'dropdown-menu-' + Date.now();
            const shift = rect.right - parentRect.right;
            if (shift > 0) {
                $target.css({
                    left: left - shift + 'px'
                });
                // move dropdown triangle to fit to open button
                $target.attr('data-uid', uid);
                tools.addCSSRule(
                    '[data-uid=' + uid + ']:before, [data-uid=' + uid + ']:after',
                    'margin-left: ' + shift + 'px'
                );
            }
        },

        /**
         * Loads emails' html
         *
         * @returns {Promise}
         */
        loadEmails: function() {
            let url;
            let promise;
            const ids = this.$(this.selectors.loadMore).addClass('process').data('emailsItems');
            if (ids) {
                url = routing.generate('oro_email_items_view', {ids: ids.join(',')});
                promise = $.ajax(url)
                    .done(this.onDoneLoadEmails.bind(this))
                    .fail(this.onFailLoadEmails.bind(this));
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
        onDoneLoadEmails: function(content) {
            if (this.disposed) {
                return;
            }
            const $content = $(content);
            this.$(this.selectors.loadMore).replaceWith($content);
            this.initEmailItemViews($content.filter(this.selectors.emailItem));
        },

        /**
         * Handles emails loading error
         */
        onFailLoadEmails: function() {
            if (this.disposed) {
                return;
            }
            this.$(this.selectors.loadMore).removeClass('process');
        },

        /**
         * Initializes EmailItemView for all passed elements
         *
         * @param {Array<jQuery.Element>} $elems
         * @return {Promise}
         */
        initEmailItemViews: function($elems) {
            const promises = _.map($elems, this._initEmailItemView, this);
            return $.when.apply(this, promises);
        },

        /**
         * Creates EmailItemView for the element and registers it as subview of the thread
         *
         * @param {HTMLElement} elem
         * @return {Promise}
         * @protected
         */
        _initEmailItemView: function(elem) {
            const emailItemView = new EmailItemView({
                autoRender: true,
                el: elem
            });
            this.subview('email:' + emailItemView.cid, emailItemView);
            this.listenTo(emailItemView, {
                toggle: this.onEmailItemToggle,
                commentCountChanged: this.onCommentCountChange
            });
            return emailItemView.getDeferredRenderPromise();
        },

        /**
         * Invokes refresh method for all emails
         */
        refreshEmails: function() {
            _.each(this.subviews, function(emailItemView) {
                emailItemView.refresh();
            });
        },

        onEmailItemToggle: function() {
            this.updateToggleAllAction();
            this.$el.trigger('content:changed');
        },

        /**
         * Update toggle all action element
         */
        updateToggleAllAction: function() {
            const hasMultipleEmails = this.$(this.selectors.emailItem).length > 1;
            const hasHiddenEmails = this._hasHiddenEmails();
            const translationPrefix = 'oro.email.thread.' + (hasHiddenEmails ? 'expand_all' : 'collapse_all');

            // update action element
            const $toggleAllAction = this.$actionPanel.find(this.selectors.toggleAll);
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
        _hasHiddenEmails: function() {
            const hasCollapsedEmails = Boolean(this.$(this.selectors.emailItem).not('.in').length);
            const hasEmailsToLoad = Boolean(this.$(this.selectors.loadMore).length);
            return hasCollapsedEmails || hasEmailsToLoad;
        },

        /**
         * Handles comments count change (added/removed)
         *
         * @param {number} diff
         */
        onCommentCountChange: function(diff) {
            this.trigger('commentCountChanged', diff);
        }
    });

    return EmailTreadView;
});
