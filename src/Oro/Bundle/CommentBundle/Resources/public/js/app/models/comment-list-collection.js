/*jslint nomen:true*/
/*global define*/

define([
    'oroui/js/app/models/base/collection',
    'underscore',
    'routing',
    'orocomment/js/app/models/comment-list-model'
], function (BaseCollection, _, routing, CommentModel) {
    'use strict';

    var CommentCollection;

    CommentCollection = BaseCollection.extend({
        model:   CommentModel,
        route: '',
        formHTML: '',
        routeParameters: {},
        filter:   {},
        pager: {
            count:    1, //total activities count
            current:  1, //current page
            pagesize: 5, //items per page
            total:    1  //total pages
        },

        url: function () {
            return routing.generate(
                this.route,
                _.extend(
                    _.extend([], this.routeParameters),
                    _.extend({page: this.getPage()}, {filter: this.filter})
                )
            );
        },

        setFilter: function (filter) {
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
        }
    });

    return CommentCollection;

});


