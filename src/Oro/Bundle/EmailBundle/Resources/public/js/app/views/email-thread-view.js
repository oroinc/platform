define(function(require) {
    'use strict';

    var EmailTreadView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var EmailItemView = require('./email-item-view');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailTreadView = BaseView.extend({
        autoRender: true,

        events: {
            'click .email-view-toggle-all': 'onToggleAllClick',
            'click .email-load-more': 'onLoadMoreClick',
            'shown.bs.dropdown .email-detailed-info-table.dropdown': 'onDetailedInfoOpen'
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
        initialize: function(options) {
            _.extend(this, _.pick(options, ['actionPanelSelector']));
            EmailTreadView.__super__.initialize.apply(this, arguments);
            this.listenTo(mediator, 'widget:doRefresh:email-thread', function() {
                if (options.isBaseView) {
                    mediator.trigger('widget:doRefresh:email-thread-context');
                }
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.$actionPanel) {
                this.$actionPanel.find(this.selectors.toggleAll).remove();
                delete this.$actionPanel;
            }
            EmailTreadView.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
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

            this._deferredRender();
            this.initEmailItemViews(this.$(this.selectors.emailItem))
                .then(_.bind(this._resolveDeferredRender, this));

            return this;
        },

        /**
         * Handles click on toggle all action element
         *
         * @param {jQuery.Event} e
         */
        onToggleAllClick: function(e) {
            this.loadEmails().done(_.bind(this.toggleAllEmails, this));
        },

        /**
         * Expands or collapses all emails
         */
        toggleAllEmails: function() {
            var show = this._hasHiddenEmails();
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
            var $target = $('>.dropdown-menu', e.currentTarget);
            var target = $target[0];
            var parentRect = this.el.getBoundingClientRect();
            $target.removeAttr('data-uid').removeClass('fixed-width').css({
                'width': '',
                'left': ''
            });
            var limitWidth = Math.min(target.clientWidth, parentRect.width);
            if (target.scrollWidth > limitWidth) {
                $target.outerWidth(limitWidth).addClass('fixed-width');
            }
            var rect = target.getBoundingClientRect();
            var left = parseInt($target.css('left'));
            var uid = 'dropdown-menu-' + Date.now();
            var shift = rect.right - parentRect.right;
            if (shift > 0) {
                $target.css({
                    'left': left - shift + 'px'
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
            var url;
            var promise;
            var ids = this.$(this.selectors.loadMore).addClass('process').data('emailsItems');
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
        onDoneLoadEmails: function(content) {
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
        onFailLoadEmails: function() {
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
         * @return {Promise}
         */
        initEmailItemViews: function($elems) {
            var promises = _.map($elems, this._initEmailItemView, this);
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
            return emailItemView.deferredRender.promise();
        },

        /**
         * Invokes refresh method for all emails
         */
        refreshEmails: function() {
            _.each(this.subviews, function(emailItemView) {
                emailItemView.refresh();
            });
        },

        /**
         * Update toggle all action element
         */
        updateToggleAllAction: function() {
            var hasMultipleEmails = this.$(this.selectors.emailItem).length > 1;
            var hasHiddenEmails = this._hasHiddenEmails();
            var translationPrefix = 'oro.email.thread.' + (hasHiddenEmails ? 'expand_all' : 'collapse_all');

            // update action element
            var $toggleAllAction = this.$actionPanel.find(this.selectors.toggleAll);
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
            var hasCollapsedEmails = Boolean(this.$(this.selectors.emailItem).not('.in').length);
            var hasEmailsToLoad = Boolean(this.$(this.selectors.loadMore).length);
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
