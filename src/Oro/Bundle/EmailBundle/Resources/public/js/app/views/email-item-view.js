define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const EmailItemView = BaseView.extend({
        events: {
            'click .email-view-toggle': 'onEmailHeadClick'
        },

        listen: {
            commentCountChanged: 'updateCommentsQuantity'
        },

        /**
         * Comments counter
         * @type {number}
         */
        commentCount: 0,

        /**
         * @inheritdoc
         */
        constructor: function EmailItemView(options) {
            EmailItemView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            this._deferredRender();
            this.initLayout().done(() => {
                const commentsComponent = this.pageComponent('comments');
                if (commentsComponent) {
                    this.commentCount = this.fetchCommentsQuantity();
                    this.listenTo(commentsComponent.collection, 'stateChange', this.onCommentsStateChange);
                    this.updateCommentsQuantity();
                }
                this._resolveDeferredRender();
            });
            return this;
        },

        /**
         * Refreshes email-body if there's email-body component
         */
        refresh: function() {
            const emailBodyComponent = this.pageComponent('email-body');
            if (emailBodyComponent) {
                emailBodyComponent.view.reattachBody();
            }
        },

        /**
         * Handles comments state change
         */
        onCommentsStateChange: function() {
            const diff = this.fetchCommentsQuantity() - this.commentCount;
            if (diff === 0) {
                return;
            }
            this.trigger('commentCountChanged', diff);
            this.commentCount += diff;
        },

        /**
         * Handles click on email head
         *  - expands or collapses full email body
         *
         * @param {jQuery.Event} e
         */
        onEmailHeadClick: function(e) {
            const exclude = 'a, .dropdown';
            const $target = this.$(e.target);
            // if the target is an action element, skip toggling the email
            if ($target.is(exclude) || $target.parents(exclude).length) {
                return;
            }

            this.toggle();
        },

        /**
         * Expands or collapses full email body
         *
         * @param {boolean=} flag expand or collapse flag (true to expand)
         */
        toggle: function(flag) {
            // if this is the last email, skip toggling
            if (this.$el.is(':last-child')) {
                return;
            }
            this.$el.toggleClass('in', flag);
            if (this.$el.hasClass('in')) {
                this.$el.find('iframe').triggerHandler('emailShown');
            }
            this.trigger('toggle', this);
        },

        /**
         * Updates visual element of comments counter
         */
        updateCommentsQuantity: function() {
            const quantity = this.fetchCommentsQuantity();
            this.$('.comment-count').toggle(Boolean(quantity));
            this.$('.comment-count .count').text(quantity);
        },

        /**
         * Fetches comments quantity related to the email
         * @returns {number}
         */
        fetchCommentsQuantity: function() {
            let quantity = null;
            const commentsComponent = this.pageComponent('comments');
            if (commentsComponent) {
                quantity = commentsComponent.collection.getState().totalItemsQuantity;
            }
            return quantity;
        }
    });

    return EmailItemView;
});
