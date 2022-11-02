define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const CommentsHeaderView = require('orocomment/js/app/views/comments-header-view');
    const CommentsNoDataView = require('orocomment/js/app/views/comments-no-data-view');
    const CommentItemView = require('orocomment/js/app/views/comment-item-view');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const template = require('text-loader!orocomment/templates/comment/comments-view.html');

    const CommentsView = BaseView.extend({
        template: template,
        events: {
            'click .add-comment-button': 'onAddCommentClick',
            'comment-edit': 'onEditComment',
            'comment-remove': 'onRemoveComment',
            'comment-load-more': 'onLoadMore'
        },

        /**
         * @inheritdoc
         */
        constructor: function CommentsView(options) {
            CommentsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.canCreate = options.canCreate;
            CommentsView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            const data = CommentsView.__super__.getTemplateData.call(this);
            data.canCreate = this.canCreate;
            return data;
        },

        render: function() {
            CommentsView.__super__.render.call(this);
            this.subview('header', new CommentsHeaderView({
                el: this.$('.comments-view-header'),
                collection: this.collection,
                canCreate: this.canCreate,
                autoRender: true
            }));
            this.subview('list', new BaseCollectionView({
                el: this.$('.comments-view-body'),
                animationDuration: 0,
                collection: this.collection,
                itemView: CommentItemView,
                autoRender: true
            }));
            this.subview('noData', new CommentsNoDataView({
                el: this.$('.comments-view-no-data'),
                collection: this.collection,
                autoRender: true
            }));
        },

        onLoadMore: function(e) {
            e.stopImmediatePropagation();
            this.trigger('loadMore');
        },

        onAddCommentClick: function(e) {
            e.stopImmediatePropagation();
            this.trigger('toAdd');
        },

        onEditComment: function(e, model) {
            e.stopImmediatePropagation();
            this.trigger('toEdit', model);
        },

        onRemoveComment: function(e, model) {
            e.stopImmediatePropagation();
            this.trigger('toRemove', model);
        }
    });

    return CommentsView;
});
