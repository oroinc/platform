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
        page: 1,
        count: 0,
        classFilters: [],

        url: function () {
            return this.baseUrl + '?page=' + this.page + '&activityClasses=' + this.getFilter();
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

        setFilter: function (classFilters) {
            this.classFilters = classFilters;
        },
        getFilter: function () {
            return this.classFilters;
        },

        getPage: function () {
            return this.page;
        },
        setPage: function (page) {
            this.page = page;
        },

        getCount: function () {
            return this.count;
        },
        setCount: function (count) {
            this.count = count;
        },

        parse: function(response) {
            this.setCount(response.count);

            return response.data;
        }
    });

    return ActivityCollection;
});
