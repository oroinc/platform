define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const loadModules = require('oroui/js/app/services/load-modules');
    const mediator = require('oroui/js/mediator');
    const ActivityView = require('../views/activity-view');
    const ActivityListView = require('../views/activity-list-view');
    const ActivityModel = require('../models/activity-list-model');
    const ActivityCollection = require('../models/activity-list-collection');
    const MultiSelectFilter = require('oro/filter/multiselect-filter');
    const DatetimeFilter = require('oro/filter/datetime-filter');
    const dataFilterWrapper = require('orofilter/js/datafilter-wrapper');

    const ActivityListComponent = BaseComponent.extend({
        defaults: {
            activityListOptions: {
                configuration: {},
                urls: {},
                routes: {},
                pager: {},
                itemView: ActivityView,
                itemModel: ActivityModel
            },
            commentOptions: {},
            activityListData: '[]',
            activityListCount: 0,
            widgetId: '',
            modules: {},
            ignoreHead: false,
            doNotFetch: false
        },

        /** @type MultiSelectFilter */
        activityTypeFilter: null,

        /** @type DatetimeFilter */
        dateRangeFilter: null,

        listen: {
            'toView collection': 'onViewActivity'
        },

        /**
         * @inheritdoc
         */
        constructor: function ActivityListComponent(options) {
            ActivityListComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = options || {};
            this.processOptions();

            if (!_.isEmpty(this.options.modules)) {
                this._deferredInit();
                loadModules(this.options.modules, function(modules) {
                    _.extend(this.options.activityListOptions, modules);
                    this._init();
                    this._resolveDeferredInit();
                }, this);
            } else {
                this._init();
            }
        },

        processOptions: function() {
            let activityListData;
            const defaults = $.extend(true, {}, this.defaults);
            _.defaults(this.options, defaults);
            _.defaults(this.options.activityListOptions, defaults.activityListOptions);
            _.defaults(this.options.commentOptions, defaults.commentOptions);

            try {
                activityListData = JSON.parse(this.options.activityListData);
            } catch (e) {
                mediator.execute('showMessage', 'error', __('oro.activitylist.load_error'));
                activityListData = {data: [], count: 0};
            }

            this.options.activityListData = activityListData.data;
            this.options.activityListCount = activityListData.count;

            this.options.activityListOptions.el = this.options._sourceElement;

            if (typeof this.options.activityListOptions.itemView === 'string') {
                this.options.modules.itemView = this.options.activityListOptions.itemView;
            }
            if (typeof this.options.activityListOptions.itemModel === 'string') {
                this.options.modules.itemModel = this.options.activityListOptions.itemModel;
            }
            this.options.activityListOptions.ignoreHead = this.options.ignoreHead;
            this.options.activityListOptions.doNotFetch = this.options.doNotFetch;
        },

        _init: function() {
            const activityOptions = this.options.activityListOptions;

            // setup activity list collection
            const collection = this.collection = new ActivityCollection(this.options.activityListData, {
                model: activityOptions.itemModel
            });
            collection.route = activityOptions.urls.route;
            collection.routeParameters = activityOptions.urls.parameters;
            if (this.options.activityListOptions.pager) {
                collection.setPageSize(this.options.activityListOptions.pager.pagesize);
                collection.setCount(this.options.activityListCount);
                collection.setSortingField(this.options.activityListOptions.pager.sortingField);
            }

            activityOptions.collection = collection;

            if (activityOptions.loadingContainerSelector) {
                activityOptions.loadingContainer = this.options._sourceElement
                    .closest(activityOptions.loadingContainerSelector).first();
            }

            // bind template for item view
            activityOptions.itemView = activityOptions.itemView.extend({// eslint-disable-line oro/named-constructor
                template: _.template($(activityOptions.itemTemplate).html())
            });

            this.listView = new ActivityListView(activityOptions);

            this.registerWidget();
        },

        /**
         * Returns filter state
         *
         * @returns {{dateRange: (*|Object), activityType: (*|Object)}}
         */
        getFilterState: function() {
            return {
                dateRange: this.dateRangeFilter.getValue(),
                activityType: this.activityTypeFilter.getValue()
            };
        },

        /**
         * Triggered when filter state is changed
         */
        onFilterStateChange: function() {
            this.listView.isFiltersEmpty = this.isFiltersEmpty();
            this.collection.setFilter(this.getFilterState());
            this.collection.resetPageFilter();
            this.collection.setPage(1);
            this.listView._reload();
        },

        isFiltersEmpty: function() {
            return (this.dateRangeFilter.isEmptyValue() && this.activityTypeFilter.isEmptyValue());
        },

        /**
         * Handles activity load event
         *
         * @param {ActivityModel} model
         */
        onViewActivity: function(model) {
            if (model.get('is_loaded') === true) {
                this.initComments(model);
            } else {
                model.once('change:contentHTML', function(model) {
                    this.initComments(model);
                }, this);
            }
        },

        /**
         * Init comments, if activity is configured to have them
         *
         * @param {ActivityModel} model
         */
        initComments: function(model) {
            const activityClass = model.getRelatedActivityClass();
            const configuration = this.options.activityListOptions.configuration[activityClass];

            if (!configuration || !configuration.has_comments) {
                // comments component is not configured for the activity
                return;
            }

            const itemView = this.listView.getItemView(model);

            // makes copy of commentOptions
            const commentOptions = $.extend(true, {}, this.options.commentOptions);
            // extend commentOptions with model related options
            _.extend(commentOptions, {
                relatedEntityId: model.get('relatedActivityId'),
                relatedEntityClassName: model.getRelatedActivityClass()
            });
            itemView.initCommentsComponent(commentOptions);
        },

        /**
         * Renders filters and binds update event
         *
         * @param $el
         */
        renderFilters: function($el) {
            let activityClass;
            let activityOptions;
            const $filterContainer = $el.find('.filter-container');

            /*
             * render "Activity Type" filter
             */
            // prepare choices
            const activityTypeChoices = {};
            const configuration = this.options.activityListOptions.configuration;
            for (activityClass in configuration) {
                if (configuration.hasOwnProperty(activityClass)) {
                    activityOptions = configuration[activityClass];
                    activityTypeChoices[activityClass] = activityOptions.label;
                }
            }

            // create and render
            this.activityTypeFilter = new MultiSelectFilter({
                label: __('oro.activitylist.widget.filter.activity.title'),
                choices: activityTypeChoices || {}
            });

            this.activityTypeFilter.render();
            this.activityTypeFilter.on('update', this.onFilterStateChange, this);
            $filterContainer.append(this.activityTypeFilter.$el);
            this.activityTypeFilter.rendered();

            /*
             * Render "Date Range" filter
             */
            // create instance
            const DateRangeFilterWithMeta =
                DatetimeFilter.extend(this.options.activityListOptions.dateRangeFilterMetadata);
            this.dateRangeFilter = new DateRangeFilterWithMeta({
                label: __('oro.activitylist.widget.filter.date_picker.title')
            });
            // tell that it should be rendered with dropdown
            _.extend(this.dateRangeFilter, dataFilterWrapper);
            // render
            this.dateRangeFilter.render();
            this.dateRangeFilter.on('update', this.onFilterStateChange, this);
            $filterContainer.append(this.dateRangeFilter.$el);
            this.dateRangeFilter.rendered();
        },

        registerWidget: function() {
            const listView = this.listView;
            mediator.execute('widgets:getByIdAsync', this.options.widgetId, widget => {
                widget.getAction('refresh', 'top', function(action) {
                    action.on('click', listView.refresh.bind(listView));
                });

                /**
                 * pager actions
                 */
                widget.getAction('goto_previous', 'top', function(action) {
                    action.on('click', listView.goto_previous.bind(listView));
                });
                widget.getAction('goto_next', 'top', function(action) {
                    action.on('click', listView.goto_next.bind(listView));
                });

                // render filters
                if (!widget.containerFilled) {
                    this.renderFilters(widget.widget);
                } else {
                    widget.on('widgetRender', this.renderFilters, this);
                }
            });
        }
    });

    return ActivityListComponent;
});
