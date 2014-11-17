/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/app/models/base/collection',
    './activity-list-model',
    'underscore',
    'routing',
], function (BaseCollection, ActivityModel, _, routing) {
    'use strict';

    var ActivityCollection;

    ActivityCollection = BaseCollection.extend({
        model:    ActivityModel,
        route: '',
        routeParameters: {},
        filter:   {},
        pager: {
            count:    1, //total activities count
            current:  1, //current page
            pagesize: 1, //items per page
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

    return ActivityCollection;
});
