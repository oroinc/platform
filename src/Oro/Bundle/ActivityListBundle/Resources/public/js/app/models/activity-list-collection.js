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
            var iPrev, iNew,
                modelCurrent, modelNew,
                correspondingModel,
                newAttributes;
            // to keep collection-view in actual state
            // need to make dirty check

            options || (options = {});
            options.previousModels = this.models;

            if (options.parse) {
                models = this.parse(models, options);
            }

            // dirty check
            iPrev = 0;
            iNew = 0;
            while (iPrev < this.models.length || iNew < models.length) {
                modelCurrent = this.models[iPrev];
                modelNew = models[iNew];

                if (!modelCurrent) {
                    // all current models are processed
                    // just add last new models
                    this.add(models.slice(iNew));
                    // mark everything is processed
                    iPrev = this.models.length;
                    iNew = models.length;
                } else if (!modelNew) {
                    // all new models are processed
                    // just remove last old models
                    this.remove(this.models.slice(iPrev));
                } else if (correspondingModel = this.find(function (item) {return item.id === modelNew.id; })) {
                    // if model has corresponding current models
                    // if updatedAt attribute was changed - replace model
                    newAttributes = modelNew instanceof this.model ? modelNew.toJSON() : modelNew;
                    if (correspondingModel.get('updatedAt') !== newAttributes.updatedAt) {
                        this.remove(correspondingModel);
                        modelNew = this._prepareModel(modelNew, options);
                        this.add(modelNew, {at: iPrev});
                    } else {
                        // remove all models before found
                        while (this.models.indexOf(correspondingModel) !== iPrev) {
                            this.remove(this.models[iPrev]);
                        }
                    }

                    iPrev++;
                    iNew++;
                } else {
                    // model is new
                    this.add(this._prepareModel(modelNew, options), {at: iPrev});
                    iPrev++;
                    iNew++;
                }
            }

            this.trigger('reset', this, options);

            return models;
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
