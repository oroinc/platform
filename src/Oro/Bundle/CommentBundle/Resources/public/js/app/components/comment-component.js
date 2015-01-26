/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        tools = require('oroui/js/tools'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        CommentFromView = require('orocomment/js/app/views/comment-form-view'),
        CommentListView = require('orocomment/js/app/views/comment-list-view'),
        CommentCollection = require('orocomment/js/app/models/comment-collection');

    CommentComponent = BaseComponent.extend({
        listen: {
            'toEdit collection': 'onCommentEdit'
        },

        initialize: function (options) {
            var collectionOptions;

            this.options = options || {};
            collectionOptions = _.pick(this.options, ['relatedEntityId', 'relatedEntityClassName', 'canCreate']);

            this.collection = new CommentCollection([], collectionOptions);

            this.listView = new CommentListView({
                el: options._sourceElement,
                collection: this.collection,
                template: options.listTemplate
            });

            this.formTemplate = options.listTemplate + '-form';
            if (this.options.canCreate) {
                this.addFormView();
            }

            this.collection.fetch();
        },

        onCommentEdit: function (model) {
            this.addFormView(model);
        },

        addFormView: function (model) {
            var formView, parentView;

            parentView = this.listView;
            if (model) {
                parentView = this.listView.getItemView(model);
            } else {
                model = new this.collection.model();
            }

            formView = new CommentFromView({
                template: this.formTemplate,
                el: parentView.$('.form-container'),
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
