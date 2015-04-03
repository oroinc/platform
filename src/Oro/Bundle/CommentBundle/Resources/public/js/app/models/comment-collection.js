/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentCollection,
        _ = require('underscore'),
        LoadMoreCollection = require('oroui/js/app/models/load-more-collection'),
        CommentModel = require('orocomment/js/app/models/comment-model');

    CommentCollection = LoadMoreCollection.extend({
        model: CommentModel,

        /**
         * @inheritDoc
         */
        routeDefaults: {
            routeName: 'oro_api_comment_get_items',
            routeQueryParameterNames: ['page', 'limit', 'createdAt', 'updatedAt']
        },

        comparator: 'createdAt',

        create: function () {
            return new CommentModel({
                relationId: this._route.get('relationId'),
                relationClass: this._route.get('relationClass')
            });
        }
    });

    return CommentCollection;

});
