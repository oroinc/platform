/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ActivityListComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        _ = require('underscore'),
        routing       = require('routing'),
        tools         = require('oroui/js/tools'),
        mediator      = require('oroui/js/mediator'),
        ActivityView       = require('../views/activity-view'),
        ActivityListView   = require('../views/activity-list-view'),
        ActivityModel      = require('../models/activity-list-model'),
        ActivityCollection = require('../models/activity-list-collection');
    require('jquery');

    ActivityListComponent = BaseComponent.extend({
        defaults: {
            activityListOptions: {
                briefTemplates: {},
                fullTemplates: {},
                urls: {},
                routes: {},
                itemView: ActivityView,
                itemModel: ActivityModel
            },
            activityListData: '[]',
            widgetId: '',
            modules: {}
        },

        initialize: function (options) {
            options = options || {};
            this.processOptions(options);

            if (!_.isEmpty(options.modules)) {
                this._deferredInit();
                tools.loadModules(options.modules, function (modules) {
                    _.extend(options.activityListOptions, modules);
                    this.initView(options);
                    this._resolveDeferredInit();
                }, this);
            } else {
                this.initView(options);
            }
        },

        processOptions: function (options) {
            var defaults;
            defaults = $.extend(true, {}, this.defaults);
            _.defaults(options, defaults);
            _.defaults(options.activityListOptions, defaults.activityListOptions);

            // map item routes to action url function
            /*
            _.each(options.activityListOptions.routes, function (route, name) {
                options.activityListOptions.urls[name + 'Item'] = function (model) {
                    return routing.generate(route, {'id': model.get('id')});
                };
            });
            delete options.activityListOptions.routes;
            */

            options.activityListData = JSON.parse(options.activityListData);
            options.activityListOptions.el = options._sourceElement;

            // collect modules which should be loaded before initialization
            _.each(['itemView', 'itemModel'], function (name) {
                if (typeof options.activityListOptions[name] === 'string') {
                    options.modules[name] = options.activityListOptions[name];
                }
            });
        },

        initView: function (options) {
            var activityOptions, collection;
            activityOptions = options.activityListOptions;

            // setup activity list collection
            collection = new ActivityCollection(options.activityListData, {
                model: activityOptions.itemModel
            });
            collection.baseUrl = activityOptions.urls.list;

            activityOptions.collection = collection;

            // bind template for item view
            activityOptions.itemView = activityOptions.itemView.extend({
                template: _.template($(activityOptions.itemTemplate).html())
            });

            this.list = new ActivityListView(activityOptions);
            this.registerWidget(options);
        },

        registerWidget: function (options) {
            var list = this.list;
            mediator.execute('widgets:getByIdAsync', options.widgetId, function (widget) {
                widget.getAction('refresh', 'adopted', function (action) {
                    action.on('click', _.bind(list.refresh, list));
                });
                /*widget.getAction('filter', 'adopted', function(action) {
                    action.on('click', _.bind(list.filter, list));
                });*/
                widget.getAction('collapse_all', 'adopted', function (action) {
                    action.on('click', _.bind(list.collapseAll, list));
                });
                widget.getAction('expand_all', 'adopted', function (action) {
                    action.on('click', _.bind(list.expandAll, list));
                });

                widget.getAction('more', 'bottom', function (action) {
                    action.on('click', _.bind(list.more, list));
                });
                /*
                widget.getAction('toggle_sorting', 'adopted', function (action) {
                    action.on('click', _.bind(list.toggleSorting, list));
                });
                */
            });
        }
    });

    return ActivityListComponent;
});
