/*jshint browser:true*/
/*jslint nomen: true*/
/*global define, require*/
define(['jquery', 'underscore', 'oro/translator', 'oro/tools', 'oro/mediator', 'oro/messenger',
    'orofilter/js/map-filter-module-name', 'oro/query-designer/filter-manager'],
function($, _, __, tools, mediator, messenger,
         mapFilterModuleName, FilterManager) {
    'use strict';

    var initialized = false,
        methods = {
            /**
             * Initializes data filters
             */
            initBuilder: function () {
                var metadata = this.$el.closest('[data-metadata]').data('metadata');
                this.metadata = _.extend({filters: []}, metadata);
                this.metadata.filters.push({
                    type: 'none',
                    templateTheme: 'embedded',
                    applicable: {},
                    popupHint: __('Choose a column first')
                });
                this.modules = {};
                methods.collectModules.call(this);
                tools.loadModules(this.modules, _.bind(methods.build, this));
            },

            /**
             * Collects required modules
             */
            collectModules: function () {
                var modules = this.modules;
                _.each(this.metadata.filters || {}, function (filter) {
                    var type = filter.type;
                    modules[type] = mapFilterModuleName(type);
                });
            },

            /**
             * Builds data filters
             */
            build: function () {
                var options = methods.combineOptions.call(this);
                var manager = new FilterManager(options);
                this.$el.prepend(manager.render().$el);
                mediator.trigger('query_designer_filter_manager_initialized', manager);
            },

            /**
             * Process metadata and combines options for filters
             *
             * @returns {Object}
             */
            combineOptions: function () {
                var filters = {},
                    modules = this.modules;
                _.each(this.metadata.filters, function (options) {
                    options.showLabel = false;
                    options.canDisable = false;
                    options.placeholder = __('Choose a condition');
                    // TODO: need refactoring of filters: options should be passed in constructor, rather than using .extend(options)
                    var Filter = modules[options.type].extend(options);
                    var filter = new Filter();
                    if (!_.isUndefined(options.templateTheme)) {
                        var templateSelector = filter.templateSelector += '-' + options.templateTheme;
                        var $template = $(filter.templateSelector);
                        if ($template.length) {
                            filter.templateSelector = templateSelector;
                            filter.template = _.template($(templateSelector).text());
                        } else {
                            messenger.notificationFlashMessage(
                                'error',
                                'The template "' + templateSelector + '" was not found.');
                        }
                    }
                    filters[options.type] = filter;
                });
                return {filters: filters};
            }
        };

    /**
     * @export  oro/query-designer/filter-builder
     * @class   oro.queryDesigner.filterBuilder
     */
    return {
        /**
         * Initializes query designer filters
         *
         * @param {jQuery} $el Container
         * @param {Function} callback A function which should be called when the initialization finished
         */
        init: function ($el, callback) {
            var initializedHandler = _.bind(function (manager) {
                initialized = true;
                callback(manager);
            }, this);
            mediator.once('query_designer_filter_manager_initialized', initializedHandler);

            methods.initBuilder.call({$el: $el});

            mediator.once('hash_navigation_request:start', function() {
                if (!initialized) {
                    mediator.off('query_designer_filter_manager_initialized', initializedHandler);
                }
            });
        }
    };
});
