define(function(require) {
    'use strict';

    var CommentComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var CommentFromView = require('orocomment/js/app/views/comment-form-view');
    var CommentsView = require('orocomment/js/app/views/comments-view');
    var CommentCollection = require('orocomment/js/app/models/comment-collection');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var DialogWidget = require('oro/dialog-widget');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

    CommentComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function CommentComponent() {
            CommentComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = options || {};

            this.collection = new CommentCollection(
                [],
                {
                    routeParameters: {
                        relationId: this.options.relatedEntityId,
                        relationClass: this.options.relatedEntityClassName
                    }
                }
            );

            this.formTemplate = options.listTemplate + '-form';

            this._deferredInit();

            this.collection.fetch();
            this.listenToOnce(this.collection, 'sync', this.onCollectionSynced);
        },

        onCollectionSynced: function() {
            this.commentsView = new CommentsView({
                el: this.options._sourceElement,
                collection: this.collection,
                autoRender: true,
                canCreate: Boolean(this.options.canCreate)
            });

            this.listenTo(this.commentsView, 'toEdit', this.onCommentEdit, this);
            this.listenTo(this.commentsView, 'toRemove', this.onCommentRemove, this);
            this.listenTo(this.commentsView, 'toAdd', this.onCommentAdd, this);
            this.listenTo(this.commentsView, 'loadMore', this.onLoadMore, this);

            this._resolveDeferredInit();
        },

        createDialog: function(title, model) {
            var loadingMaskView;
            var dialogWidget = new DialogWidget({
                title: title,
                el: $('<div><div class="comment-form-container" data-layout="separate"></div>'),
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    modal: true,
                    resizable: false,
                    width: '510px',
                    dialogClass: 'add-comment-dialog'
                }
            });
            // init form view
            this._initFormView(dialogWidget, model);

            dialogWidget.render();

            // bind dialog loader
            loadingMaskView = new LoadingMaskView({
                container: dialogWidget.loadingElement
            });
            dialogWidget.subview('loading', loadingMaskView);
            loadingMaskView.listenTo(model, 'request', loadingMaskView.show);
            loadingMaskView.listenTo(model, 'sync error', loadingMaskView.hide);

            return dialogWidget;
        },

        onCommentAdd: function() {
            if (!this.options.canCreate) {
                return;
            }

            var model = this.collection.create();

            // init dialog
            var dialogWidget = this.createDialog(__('oro.comment.dialog.add_comment.title'), model);

            model.once('sync', function() {
                dialogWidget.remove();
                // add item to collection after it is stored on server
                this.collection.add(model);
            }, this);
        },

        onCommentEdit: function(model) {
            var dialogWidget;

            if (!model.get('editable')) {
                return;
            }

            // init dialog
            dialogWidget = this.createDialog(__('oro.comment.dialog.edit_comment.title'), model);

            dialogWidget.listenTo(model, 'sync', _.bind(function() {
                dialogWidget.remove();
            }, this));
        },

        onCommentRemove: function(model) {
            if (!model.get('removable')) {
                return;
            }

            var confirm = new DeleteConfirmation({
                content: __('oro.comment.deleteConfirmation')
            });

            confirm.on('ok', _.bind(function() {
                model.destroy();
            }, this));

            confirm.open();
        },

        onLoadMore: function() {
            this.collection.loadMore();
        },

        _initFormView: function(parentView, model) {
            var formView;
            formView = new CommentFromView({
                template: this.formTemplate,
                el: parentView.$('.comment-form-container'),
                model: model
            });
            parentView.subview('form', formView);
            this.listenTo(formView, 'submit', this.onFormSubmit, this);
        },

        onFormSubmit: function(formView) {
            var model = formView.model;

            var options = formView.fetchAjaxOptions({
                url: model.url()
            });

            model.save(null, options);
        }
    });

    return CommentComponent;
});
