define(function(require) {
    'use strict';

    var GridSidebarComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var PageableCollection = require('orodatagrid/js/pageable-collection');
    var layoutHelper = require('oroui/js/tools/layout-helper');

    GridSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            container: '',
            sidebar: '',
            sidebarAlias: '',
            widgetAlias: '',
            widgetContainer: '',
            widgetRoute: 'oro_datagrid_widget',
            widgetRouteParameters: {
                gridName: ''
            },
            gridParam: 'grid',
            fixSidebarHeight: false
        },

        /**
         * @property {Object}
         */
        listen: {
            'grid_load:complete mediator': 'onGridLoadComplete'
        },

        /**
         * @property {Object}
         */
        $container: {},

        /**
         * @property {Object}
         */
        gridCollection: {},

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = options._sourceElement;
            this.$widgetContainer = $(options.widgetContainer);

            mediator.on('grid-sidebar:change:' + this.options.sidebarAlias, this.onSidebarChange, this);

            this.$container.find('.control-minimize').click(_.bind(this.minimize, this));
            this.$container.find('.control-maximize').click(_.bind(this.maximize, this));

            if (this.options.fixSidebarHeight) {
                layoutHelper.setAvailableHeight('.' + this.options.sidebar);
            }

            this._maximizeOrMaximize(null);
        },

        /**
         * @param {Object} collection
         */
        onGridLoadComplete: function(collection) {
            if (collection.inputName === this.options.widgetRouteParameters.gridName) {
                this.gridCollection = collection;

                var self = this;
                widgetManager.getWidgetInstanceByAlias(
                    this.options.widgetAlias,
                    function() {
                        self._patchGridCollectionUrl(self._getQueryParamsFromUrl(location.search));
                    }
                );
            }
        },

        /**
         * @param {Object} data
         */
        onSidebarChange: function(data) {
            var params = _.extend(
                this._getQueryParamsFromUrl(location.search),
                data.params,
                this._getDatagridParams()
            );
            var widgetParams = _.extend(
                _.omit(this.options.widgetRouteParameters, this.options.gridParam),
                params
            );
            var self = this;

            this._pushState(_.omit(params, _.isNull));

            this._patchGridCollectionUrl(params);

            widgetManager.getWidgetInstanceByAlias(
                this.options.widgetAlias,
                function(widget) {
                    widget.setUrl(routing.generate(self.options.widgetRoute, widgetParams));

                    if (data.widgetReload) {
                        widget.render();
                    } else {
                        mediator.trigger('datagrid:doRefresh:' + widgetParams.gridName);
                    }
                }
            );
        },

        /**
         * @param {Object} params
         * @private
         */
        _patchGridCollectionUrl: function(params) {
            var collection = this.gridCollection;
            if (!_.isUndefined(collection)) {
                var url = collection.url;
                if (_.isUndefined(url)) {
                    return;
                }
                var newParams = _.extend(
                    this._getQueryParamsFromUrl(url),
                    _.omit(params, this.options.gridParam)
                );
                if (url.indexOf('?') !== -1) {
                    url = url.substring(0, url.indexOf('?'));
                }
                if (!_.isEmpty(newParams)) {
                    collection.url = url + '?' + this._urlParamsToString(newParams);
                }
            }
        },

        /**
         * @private
         * @param {Object} params
         */
        _pushState: function(params) {
            var paramsString = this._urlParamsToString(_.omit(params, ['saveState']));
            var current = mediator.execute('pageCache:getCurrent');
            mediator.execute('changeUrl', current.path + '?' + paramsString);
        },

        minimize: function() {
            this._maximizeOrMaximize('off');
        },

        maximize: function() {
            this._maximizeOrMaximize('on');
        },

        /**
         * @private
         * @param {String} state
         */
        _maximizeOrMaximize: function(state) {
            var params = this._getQueryParamsFromUrl(location.search);

            if (state === null) {
                state = params.sidebar || 'on';
            }

            if (state === 'on') {
                this.$container.addClass('grid-sidebar-maximized').removeClass('grid-sidebar-minimized');
                this.$widgetContainer.addClass('grid-sidebar-maximized').removeClass('grid-sidebar-minimized');

                delete params.sidebar;
            } else {
                this.$container.addClass('grid-sidebar-minimized').removeClass('grid-sidebar-maximized');
                this.$widgetContainer.addClass('grid-sidebar-minimized').removeClass('grid-sidebar-maximized');

                params.sidebar = state;
            }

            this._pushState(params);
        },

        /**
         * @param {String} url
         * @return {Object}
         * @private
         */
        _getQueryParamsFromUrl: function(url) {
            if (_.isUndefined(url)) {
                return {};
            }

            if (url.indexOf('?') === -1) {
                return {};
            }

            var query = url.substring(url.indexOf('?') + 1, url.length);
            if (!query.length) {
                return {};
            }

            return PageableCollection.decodeStateData(query);
        },

        /**
         * @param {Object} params
         * @return {String}
         * @private
         */
        _urlParamsToString: function(params) {
            return $.param(params);
        },

        /**
         * @returns {Object}
         * @private
         */
        _getDatagridParams: function() {
            var params = {};
            params[this.gridCollection.options.gridName] = this.gridCollection.urlParams;

            return params;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('grid-sidebar:change:' + this.options.sidebarAlias);

            delete this.gridCollection;

            GridSidebarComponent.__super__.dispose.call(this);
        }
    });

    return GridSidebarComponent;
});
