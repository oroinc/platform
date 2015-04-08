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

        reset: function (models, options) {
            var i, newModel, oldModel;
            if (options.parse) {
                for (i = 0; i < models.data.length; i++) {
                    newModel = models.data[i];
                    oldModel = this.get(newModel.id);
                    // if model is in collection
                    if (oldModel && oldModel.isSameActivity(newModel)) {
                        // and if there was no updates
                        if (oldModel.get('updatedAt') === newModel.updatedAt) {
                            // use old model
                            models.data[i] = oldModel;
                        }
                    }
                }
            }

            return ActivityCollection.__super__.reset.call(this, models, options);
        },

        /**
         * Finds the same model in collection
         *
         * @param model {Object|ActivityModel} attributes or model to compare
         */
        findSameActivity: function (model) {
            return this.find(function (item) {
                return item.isSameActivity(model);
            });
        },

        getCount: function () {
            return parseInt(this.pager.count);
        },

        setCount: function (count) {
            this.pager.count = count;
            this.pager.total = count == 0 ? 1 : Math.ceil(count/this.pager.pagesize);

            this.count = count;
        },

        parse: function (response) {
            this.setCount(parseInt(response.count));

            return response.data;
        }
    });

    return ActivityCollection;
});
