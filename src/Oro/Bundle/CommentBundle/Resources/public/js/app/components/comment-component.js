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
        listen: {
            'toEdit commentsView': 'onCommentEdit',
            'toRemove commentsView': 'onCommentRemove',
            'toAdd commentsView': 'onCommentAdd'
        },

        initialize: function (options) {
            var collectionRouteOptions;

            this.options = options || {};

            this.collection = new CommentCollection(
                [],
                {
                    routeParams: {
                        relationId: this.options.relatedEntityId,
                        relationClass: this.options.relatedEntityClassName
                    }
                }
            );

            this.commentsView = new CommentsView({
                el: options._sourceElement,
                collection: this.collection,
                autoRender: true,
                canCreate: Boolean(this.options.canCreate)
            });

            this.formTemplate = options.listTemplate + '-form';

            this._deferredInit();

            this.collection.fetch();
            this.collection.once('sync', this._resolveDeferredInit, this);
        },

        onCommentAdd: function () {
            var dialogWidget, loadingMaskView, model;
            if (!this.options.canCreate) {
                return;
            }

            model = this.collection.create();

            // init dialog
            dialogWidget = new DialogWidget({
                title: __('oro.comment.dialog.add_comment.title'),
                el: $('<div><div class="comment-form-container"/></div>'),
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    modal: true,
                    width: '510px',
                    dialogClass: 'add-comment-dialog'
                }
            });
            model.once('sync', function () {
                dialogWidget.remove();
                // update collection
                this.collection.add(model);
                this.collection.state.set({
                    limit: this.collection.state.get('limit') + 1,
                    count: this.collection.state.get('count') + 1
                }, {silent: true});
                this.collection.sort();
            }, this);

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
        },

        onCommentEdit: function (model) {
            var dialogWidget, loadingMaskView;
            if (!this.options.canCreate) {
                return;
            }

            // init dialog
            dialogWidget = new DialogWidget({
                title: __('oro.comment.dialog.edit_comment.title'),
                el: $('<div><div class="comment-form-container"/></div>'),
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    modal: true,
                    width: '510px',
                    dialogClass: 'add-comment-dialog'
                }
            });

            dialogWidget.listenTo(model, 'sync', _.bind(function () {
                dialogWidget.remove();
            }, this));

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
        },

        onCommentRemove: function (model) {
            var confirm = new DeleteConfirmation({
                content: __('oro.comment.deleteConfirmation')
            });

            confirm.on('ok', _.bind(function () {
                model.destroy({error: function () {
                    mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
                }});
                this.collection.state.set({
                    limit: this.collection.state.get('limit') - 1,
                    count: this.collection.state.get('count') - 1
                }, {silent: true});
            }, this));

            confirm.open();
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

            if (model.isNew()) {
                options.success = _.bind(function () {
                    this.collection.add(model, {at: 0});
                }, this);
            }

            model.save(null, options);
        }
    });


    return CommentComponent;
});
