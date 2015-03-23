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

        comparator: 'createdAt',

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


