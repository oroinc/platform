define(function(require) {
    'use strict';

    var CommentsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var CommentsHeaderView = require('orocomment/js/app/views/comments-header-view');
    var CommentsNoDataView = require('orocomment/js/app/views/comments-no-data-view');
    var CommentItemView = require('orocomment/js/app/views/comment-item-view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var template = require('text!orocomment/templates/comment/comments-view.html');

    CommentsView = BaseView.extend({
        template: template,
        events: {
            'click .add-comment-button': 'onAddCommentClick',
            'comment-edit': 'onEditComment',
            'comment-remove': 'onRemoveComment',
            'comment-load-more': 'onLoadMore'
        },

        /**
         * @inheritDoc
         */
        constructor: function CommentsView() {
            CommentsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.canCreate = options.canCreate;
            CommentsView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = CommentsView.__super__.getTemplateData.apply(this, arguments);
            data.canCreate = this.canCreate;
            return data;
        },

        render: function() {
            CommentsView.__super__.render.apply(this, arguments);
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
