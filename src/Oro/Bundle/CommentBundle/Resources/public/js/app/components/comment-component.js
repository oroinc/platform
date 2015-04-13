/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        CommentFromView = require('orocomment/js/app/views/comment-form-view'),
        CommentsView = require('orocomment/js/app/views/comments-view'),
        CommentCollection = require('orocomment/js/app/models/comment-collection'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        DialogWidget = require('oro/dialog-widget'),
        DeleteConfirmation = require('oroui/js/delete-confirmation');

    CommentComponent = BaseComponent.extend({

        initialize: function (options) {
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
            this.collection.once('sync', this.onCollectionSynced, this);
        },

        onCollectionSynced: function () {
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

        createDialog: function (title, model) {
            var dialogWidget, loadingMaskView;
            dialogWidget = new DialogWidget({
                title: title,
                el: $('<div><div class="comment-form-container"/></div>'),
                stateEnabled: false,
                incrementalPosition: false,
                resizable: false,
                dialogOptions: {
                    modal: true,
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

        onCommentAdd: function () {
            var dialogWidget, model;
            if (!this.options.canCreate) {
                return;
            }

            model = this.collection.create();

            // init dialog
            dialogWidget = this.createDialog(__('oro.comment.dialog.add_comment.title'), model);

            model.once('sync', function () {
                dialogWidget.remove();
                // add item to collection after it is stored on server
                this.collection.add(model);
            }, this);
        },

        onCommentEdit: function (model) {
            var dialogWidget;

            if (!model.get('editable')) {
                return;
            }

            // init dialog
            dialogWidget = this.createDialog(__('oro.comment.dialog.edit_comment.title'), model);

            dialogWidget.listenTo(model, 'sync', _.bind(function () {
                dialogWidget.remove();
            }, this));
        },

        onCommentRemove: function (model) {
            if (!model.get('removable')) {
                return;
            }

            var confirm = new DeleteConfirmation({
                content: __('oro.comment.deleteConfirmation')
            });

            confirm.on('ok', _.bind(function () {
                model.destroy({error: function () {
                    mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
                }});
            }, this));

            confirm.open();
        },

        onLoadMore: function () {
            this.collection.loadMore();
        },

        _initFormView: function (parentView, model) {
            var formView;
            formView = new CommentFromView({
                template: this.formTemplate,
                el: parentView.$('.comment-form-container'),
                model: model
            });
            parentView.subview('form', formView);
            this.listenTo(formView, 'submit', this.onFormSubmit, this);
        },

        onFormSubmit: function (formView) {
            var model, options;

            model = formView.model;

            options = formView.fetchAjaxOptions({
                url: model.url()
            });

            model.save(null, options);
        }
    });


    return CommentComponent;
});
