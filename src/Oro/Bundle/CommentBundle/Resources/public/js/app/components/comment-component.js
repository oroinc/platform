/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        CommentFromView = require('orocomment/js/app/views/comment-form-view'),
        CommentListView = require('orocomment/js/app/views/comment-list-view'),
        CommentCollection = require('orocomment/js/app/models/comment-collection'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        DialogWidget = require('oro/dialog-widget');

    CommentComponent = BaseComponent.extend({
        listen: {
            'toEdit collection': 'onCommentEdit',
            'toAdd collection': 'onCommentAdd'
        },

        initialize: function (options) {
            var collectionOptions;

            this.options = options || {};
            collectionOptions = _.pick(this.options, ['relatedEntityId', 'relatedEntityClassName', 'canCreate']);

            this.collection = new CommentCollection([], collectionOptions);

            this.listView = new CommentListView({
                el: options._sourceElement,
                collection: this.collection,
                template: options.listTemplate,
                canCreate: this.options.canCreate
            });

            this.formTemplate = options.listTemplate + '-form';

            this.collection.fetch();
        },

        onCommentAdd: function () {
            var dialogWidget, loadingMaskView, model;
            if (!this.options.canCreate) {
                return;
            }

            model = new this.collection.model();

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
            model.once('synced', function () {
                dialogWidget.remove();
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
        },

        onCommentEdit: function (model) {
            var parentView;
            if (!model.get('editable')) {
                return;
            }
            parentView = this.listView.getItemView(model);
            this._initFormView(parentView, model);
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
            this.listenTo(formView, 'reset', this.onFormReset, this);
        },

        onFormSubmit: function (formView) {
            var model, listView, options;

            listView = this.listView;
            model = formView.model;

            if (model.isNew()) {
                this.collection.add(model, {at: 0});
            }

            model.once('sync', function () {
                var itemView = listView.getItemView(model);
                if (itemView) {
                    itemView.render();
                }
            });

            options = formView.fetchAjaxOptions({
                url: model.url()
            });
            model.save(null, options);
        },

        onFormReset: function (formView) {
            var model, itemView;
            model = formView.model;
            if (!model.isNew()) {
                itemView = this.listView.getItemView(model);
                itemView.render();
            }
        }
    });


    return CommentComponent;
});
