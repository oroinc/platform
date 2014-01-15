/* jshint browser:true */
/* global define, require */
define(['jquery', 'underscore', 'oro/tools', 'oro/mediator'],
function($, _, tools,  mediator, FiltersManager) {
    'use strict';
    var initialized = false,
        methods = {
            initBuilder: function () {
                this.data     = _.extend({totals: {}}, this.$el.data('data'));
                this.modules  = {};

                //methods.collectModules.call(this);
                tools.loadModules(this.modules, _.bind(methods.build, this));
            },

            build: function () {
                methods.combineOptions.call(this);
                //options.collection = this.collection;
                //var filtersList = new FiltersManager(options);
                //this.$el.prepend(filtersList.render().$el);

                //mediator.trigger('datagrid_filters:rendered', this.collection);
                //mediator.trigger("datagrid_collection_set_after", this.collection);

                //if (this.collection.length === 0) {
                    //filtersList.$el.hide();
                //}
            },

            /**
             * Process metadata and combines options for filters
             *
             * @returns {Object}
             */
            combineOptions: function () {
                var totals     = {},
                    modules    = this.modules,
                    collection = this.collection;

                //debugger;
                _.each(this.data.options.totals, function (total, key) {

                    /*if (_.has(options, 'name') && _.has(options, 'type')) {
                        // @TODO pass collection only for specific filters
                        if (options.type == 'selectrow') {
                            options.collection = collection
                        }
                        filters[options.name] = new (modules[options.type].extend(options));
                    }*/
                        //console.log(key, total);
                        //totals[options.name] = [];
                        //self.totals[key].total = total.total;
                        collection.state.totals[key].total = total.total;
                });

                return {totals: collection.state.totals};
            }
        },

        initHandler = function (collection, $el) {
            methods.initBuilder.call({$el: $el, collection: collection});
            initialized = true;
        };

    return {
        init: function () {
            //initialized = false;

            mediator.once('datagrid_collection_set_after', initHandler);
            /*mediator.once('hash_navigation_request:start', function() {
                if (!initialized) {
                    mediator.off('datagrid_collection_set_after', initHandler);
                }
            });*/
        }
    };
});