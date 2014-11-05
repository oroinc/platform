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

        url: function () {
            return this.baseUrl + '?sorting=' + this.sorting;
        },

        getSorting: function () {
            return this.sorting;
        },

        setSorting: function (mode) {
            this.sorting = mode;
        },

        getFromDate: function () {
        },

        setFromDate: function () {
        },

        getToDate: function () {
        },

        setToDate: function () {
        },

        getFilter: function () {
        },

        setFilter: function () {
        }
    });

    return ActivityCollection;
});
