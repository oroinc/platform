define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const CommentFromView = require('orocomment/js/app/views/comment-form-view');
    const CommentsView = require('orocomment/js/app/views/comments-view');
    const CommentCollection = require('orocomment/js/app/models/comment-collection');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const DialogWidget = require('oro/dialog-widget');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');

    const CommentComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function CommentComponent(options) {
            CommentComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
            const dialogWidget = new DialogWidget({
                title: title,
                el: $('<div class="widget-content"><div class="comment-form-container" data-layout="separate"></div>'),
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
            const loadingMaskView = new LoadingMaskView({
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

            const model = this.collection.create();

            // init dialog
            const dialogWidget = this.createDialog(__('oro.comment.dialog.add_comment.title'), model);

            model.once('sync', function() {
                dialogWidget.remove();
                // add item to collection after it is stored on server
                this.collection.add(model);
            }, this);
        },

        onCommentEdit: function(model) {
            if (!model.get('editable')) {
                return;
            }

            // init dialog
            const dialogWidget = this.createDialog(__('oro.comment.dialog.edit_comment.title'), model);

            dialogWidget.listenTo(model, 'sync', () => {
                dialogWidget.remove();
            });
        },

        onCommentRemove: function(model) {
            if (!model.get('removable')) {
                return;
            }

            const confirm = new DeleteConfirmation({
                content: __('oro.comment.deleteConfirmation')
            });

            confirm.on('ok', () => {
                model.destroy();
            });

            confirm.open();
        },

        onLoadMore: function() {
            this.collection.loadMore();
        },

        _initFormView: function(parentView, model) {
            const formView = new CommentFromView({
                template: this.formTemplate,
                el: parentView.$('.comment-form-container'),
                model: model
            });
            parentView.subview('form', formView);
            this.listenTo(formView, 'submit', this.onFormSubmit, this);
        },

        onFormSubmit: function(formView) {
            const model = formView.model;

            const options = formView.fetchAjaxOptions({
                url: model.url()
            });

            model.save(null, options);
        }
    });

    return CommentComponent;
});
