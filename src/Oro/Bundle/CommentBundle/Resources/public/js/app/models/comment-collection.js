/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentCollection,
        Chaplin = require('chaplin'),
        _ = require('underscore'),
        routing = require('routing'),
        mediator = require('oroui/js/mediator'),
        __ = require('orotranslation/js/translator'),
        LoadMoreCollection = require('oroui/js/app/models/loadmore-collection'),
        CommentModel = require('orocomment/js/app/models/comment-model');

    CommentCollection = LoadMoreCollection.extend({
        model: CommentModel,
        routeName: 'oro_api_comment_get_items',

        initialize: function (models, options) {
            // handel collection size changes
            this.on('add', this.onAddNewRecord, this);
            this.on('remove', this.onRemoveRecord, this);
            this.on('error', this.onErrorResponse, this);

            CommentCollection.__super__.initialize.apply(this, arguments);
        },

        onRemoveRecord: function (model, collection, options) {
            model.once('sync', function () {
                collection.setRecordsQuantity(collection.getRecordsQuantity() - 1);
                collection.setPage(collection.getPage());
                collection.fetch();
            });
        },

        onErrorResponse: function (collection, jqxhr, options) {
            this.finishSync();
            if (jqxhr.status === 403) {
                mediator.execute('showFlashMessage', 'error', __('oro.ui.forbidden_error'));
            } else if (jqxhr.status !== 400) {
                // 400 response is handled by form view
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
            }
        },

        getPage: function () {
            return this.state.page;
        },

        setPage: function (page) {
            var pages = this.getPagesQuantity();
            if (page <= 0) {
                page = 1;
            } else if (page > pages) {
                page = pages;
            }
            if (this.state.page !== page) {
                this.state.page = page;
                this.fetch();
            }
        },

        getPagesQuantity: function () {
            return Math.ceil(this.state.itemsQuantity / this.state.itemPerPage) || 1;
        },

        setRecordsQuantity: function (quantity) {
            return this.state.itemsQuantity = quantity;
        },

        getRecordsQuantity: function () {
            return this.state.itemsQuantity;
        },

        createComment: function () {
            return new CommentModel({}, {
                relatedEntityClassName: this.relatedEntityClassName,
                relatedEntityId: this.relatedEntityId
            });
        }
    });

    _.extend(CommentCollection.prototype, Chaplin.SyncMachine);

    return CommentCollection;

});


