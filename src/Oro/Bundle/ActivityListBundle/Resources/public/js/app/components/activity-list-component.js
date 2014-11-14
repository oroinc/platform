/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ActivityListComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        routing       = require('routing'),
        tools         = require('oroui/js/tools'),
        mediator      = require('oroui/js/mediator'),
        ActivityView       = require('../views/activity-view'),
        ActivityListView   = require('../views/activity-list-view'),
        ActivityModel      = require('../models/activity-list-model'),
        ActivityCollection = require('../models/activity-list-collection'),
        MultiSelectFilter  = require('oro/filter/multiselect-filter'),
        DatetimeFilter     = require('oro/filter/datetime-filter'),
        dataFilterWrapper  = require('orofilter/js/datafilter-wrapper');
    require('jquery');

    ActivityListComponent = BaseComponent.extend({
        defaults: {
            activityListOptions: {
                configuration: {},
                urls: {},
                routes: {},
                pager: {},
                itemView: ActivityView,
                itemModel: ActivityModel
            },
            activityListData: '[]',
            activityListCount: 0,
            widgetId: '',
            modules: {}
        },

        /** @type MultiSelectFilter */
        activityTypeFilter: null,

        /** @type DatetimeFilter */
        dateRangeFilter: null,

        initialize: function (options) {
            this.options = options || {};
            this.processOptions();

            if (!_.isEmpty(this.options.modules)) {
                this._deferredInit();
                tools.loadModules(this.options.modules, function (modules) {
                    _.extend(this.options.activityListOptions, modules);
                    this.initView();
                    this._resolveDeferredInit();
                }, this);
            } else {
                this.initView();
            }
        },

        processOptions: function () {
            var defaults;
            defaults = $.extend(true, {}, this.defaults);
            _.defaults(this.options, defaults);
            _.defaults(this.options.activityListOptions, defaults.activityListOptions);

            var activityListData = JSON.parse(this.options.activityListData);
            this.options.activityListData  = activityListData.data;
            this.options.activityListCount = activityListData.count;

            this.options.activityListOptions.el = this.options._sourceElement;

            if (typeof this.options.activityListOptions.itemView === 'string') {
                this.options.modules.itemView = this.options.activityListOptions.itemView;
            }
            if (typeof this.options.activityListOptions.itemModel === 'string') {
                this.options.modules.itemModel = this.options.activityListOptions.itemModel;
            }
        },

        initView: function () {
            var activityOptions, collection;
            activityOptions = this.options.activityListOptions;

            // setup activity list collection
            collection = new ActivityCollection(this.options.activityListData, {
                model: activityOptions.itemModel
            });
            collection.baseUrl = activityOptions.urls.list;
            collection.setPageSize(this.options.activityListOptions.pager.pagesize);
            collection.setCount(this.options.activityListCount);

            activityOptions.collection = collection;

            // bind template for item view
            activityOptions.itemView = activityOptions.itemView.extend({
                template: _.template($(activityOptions.itemTemplate).html())
            });

            this.list = new ActivityListView(activityOptions);

            this.registerWidget();
        },

        /**
         * Returns filter state
         *
         * @returns {{dateRange: (*|Object), activityType: (*|Object)}}
         */
        getFilterState: function () {
            return {
                dateRange: this.dateRangeFilter.getValue(),
                activityType: this.activityTypeFilter.getValue()
            }
        },

        /**
         * Triggered when filter state is changed
         */
        onFilterStateChange: function () {
            console.log(this.getFilterState());
        },

        /**
         * Renders filters and binds update event
         *
         * @param $el
         */
        renderFilters: function ($el) {
            var activityClass, activityOptions, activityTypeChoices, DateRangeFilterWithMeta;

            /*
             * render "Activity Type" filter
             */
            // prepare choices
            activityTypeChoices = {};
            for (activityClass in this.options.activityListOptions.configuration) {
                activityOptions = this.options.activityListOptions.configuration[activityClass];
                activityTypeChoices[activityClass] = activityOptions.label;
            }

            // create and render
            this.activityTypeFilter = new MultiSelectFilter({
                'label': __('oro.activitylist.widget.filter.activity.title'),
                'choices': activityTypeChoices || {}
            });

            this.activityTypeFilter.render();
            this.activityTypeFilter.on('update', this.onFilterStateChange, this);
            $el.find('.activity-type-filter').append(this.activityTypeFilter.$el);

            /*
             * Render "Date Range" filter
             */
            // create instance
            DateRangeFilterWithMeta = DatetimeFilter.extend(this.options.activityListOptions.dateRangeFilterMetadata);
            this.dateRangeFilter = new DateRangeFilterWithMeta({
                'label': __('oro.activitylist.widget.filter.date_picker.title')
            });
            // tell that it should be rendered with dropdown
            _.extend(this.dateRangeFilter, dataFilterWrapper);
            // render
            this.dateRangeFilter.render();
            this.dateRangeFilter.on('update', this.onFilterStateChange, this);
            $el.find('.date-range-filter').append(this.dateRangeFilter.$el)
        },

        registerWidget: function () {
            var list = this.list;
            mediator.execute('widgets:getByIdAsync', this.options.widgetId, _.bind(function (widget) {
                widget.getAction('refresh', 'adopted', function (action) {
                    action.on('click', _.bind(list.refresh, list));
                });
                widget.getAction('more', 'bottom', function (action) {
                    action.on('click', _.bind(list.more, list));
                });

                /**
                 * pager actions
                 */
                widget.getAction('goto_first', 'bottom', function (action) {
                    action.on('click', _.bind(list.goto_first, list));
                });
                widget.getAction('goto_next', 'bottom', function (action) {
                    action.on('click', _.bind(list.goto_next, list));
                });
                widget.getAction('goto_previous', 'bottom', function (action) {
                    action.on('click', _.bind(list.goto_previous, list));
                });
                widget.getAction('goto_last', 'bottom', function (action) {
                    action.on('click', _.bind(list.goto_last, list));
                });

                // render filters
                if (!widget.containerFilled) {
                    this.renderFilters(widget.widget);
                } else {
                    widget.on('widgetRender', this.renderFilters, this);
                }
            }, this));
        }
    });

    return ActivityListComponent;
});
