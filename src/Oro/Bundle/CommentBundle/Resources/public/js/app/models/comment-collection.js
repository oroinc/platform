/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentCollection,
        _ = require('underscore'),
        routing = require('routing'),
        BaseCollection = require('oroui/js/app/models/base/collection'),
        CommentModel = require('orocomment/js/app/models/comment-model');

    CommentCollection = BaseCollection.extend({
        model: CommentModel,
        route: 'oro_api_comment_get_items',
        /*route: '',
        formHTML: '',
        routeParameters: {},
        filter:   {},
        pager: {
            count:    1, //total activities count
            current:  1, //current page
            pagesize: 5, //items per page
            total:    1  //total pages
        },
*/
        initialize: function (models, options) {
            _.extend(this, _.pick(options, ['relatedEntityId', 'relatedEntityClassName', 'formName']));
            CommentCollection.__super__.initialize.apply(this, arguments);
        },

        url: function () {
            var options = {
                relationId:    this.relatedEntityId,
                relationClass: this.relatedEntityClassName
            };
            return routing.generate(this.route, options);
        }

       /* setFilter: function (filter) {
            this.filter = filter;
        },

        getPage: function () {
            return parseInt(this.pager.current);
        },
        setPage: function (page) {
            this.pager.current = page;
        },

        getPageSize: function () {
            return parseInt(this.pager.pagesize);
        },
        setPageSize: function (pagesize) {
            this.pager.pagesize = pagesize;
        },

        getCount: function () {
            return parseInt(this.pager.count);
        },
        setCount: function (count) {
            this.pager.count = count;
            this.pager.total = count == 0 ? 1 : Math.ceil(count/this.pager.pagesize);

            this.count = count;
        },

        parse: function(response) {
            this.setCount(parseInt(response.count));

            return response.data;
        },*/
    });

    return CommentCollection;

});


