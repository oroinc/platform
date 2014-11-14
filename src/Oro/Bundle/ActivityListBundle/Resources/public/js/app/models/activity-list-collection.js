/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/app/models/base/collection',
    './activity-list-model'
], function (BaseCollection, ActivityModel) {
    'use strict';

    var ActivityCollection;

    ActivityCollection = BaseCollection.extend({
        model:    ActivityModel,
        baseUrl:  '',
        sorting:  'DESC',
        fromDate: '',
        toDate:   '',
        filter:   '',
        pager: {
            count:    1, //total activities count
            current:  1, //current page
            pagesize: 1, //items per page
            total:    1  //total pages
        },

        url: function () {
            return this.baseUrl + '?page=' + this.getPage();
        },

        getSorting: function () {
            return this.sorting;
        },
        setSorting: function (mode) {
            this.sorting = mode;
        },

        getFromDate: function () {},
        setFromDate: function () {},

        getToDate: function () {},
        setToDate: function () {},

        getFilter: function () {},
        setFilter: function () {},

        getPage: function () {
            return this.pager.current;
        },
        setPage: function (page) {
            this.pager.current = page;
        },

        getPageSize: function () {
            return this.pager.pagesize;
        },
        setPageSize: function (pagesize) {
            this.pager.pagesize = pagesize;
        },

        getCount: function () {
            return this.pager.count;
        },
        setCount: function (count) {
            this.pager.count = count;
            this.pager.total = Math.ceil(count/this.pager.pagesize);

            this.count = count;
        },

        parse: function(response) {
            this.setCount(response.count);

            return response.data;
        }
    });

    return ActivityCollection;
});
