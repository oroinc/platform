/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentCollection,
        Chaplin = require('chaplin'),
        _ = require('underscore'),
        LoadMoreCollection = require('oroui/js/app/models/loadmore-collection'),
        CommentModel = require('orocomment/js/app/models/comment-model');

    CommentCollection = LoadMoreCollection.extend({
        model: CommentModel,
        routeName: 'oro_api_comment_get_items',
        routeAccepts: ['page', 'limit', 'createdAt', 'updatedAt'],

        comparator: 'createdAt',

        create: function () {
            return new CommentModel({
                relationId: this._route.get('relationId'),
                relationClass: this._route.get('relationClass')
            });
        }
    });

    _.extend(CommentCollection.prototype, Chaplin.SyncMachine);

    return CommentCollection;

});


