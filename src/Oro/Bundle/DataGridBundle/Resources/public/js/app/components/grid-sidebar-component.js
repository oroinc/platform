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

    GridSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            sidebarAlias: '',
            widgetAlias: '',
            widgetRoute: 'oro_datagrid_widget',
            widgetRouteParameters: {
                gridName: ''
            },
            gridParam: 'grid'
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
        gridCollection: {},

        /**
         * @inheritDoc
         */
        constructor: function GridSidebarComponent() {
            GridSidebarComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            mediator.on('grid-sidebar:change:' + this.options.sidebarAlias, this.onSidebarChange, this);
        },

        /**
         * @param {Object} collection
         * @param {Object} gridElement
         */
        onGridLoadComplete: function(collection, gridElement) {
            if (collection.inputName === this.options.widgetRouteParameters.gridName) {
                this.gridCollection = collection;

                var self = this;
                widgetManager.getWidgetInstanceByAlias(
                    this.options.widgetAlias,
                    function() {
                        self._patchGridCollectionUrl(self._getQueryParamsFromUrl(location.search));
                    }
                );

                var foundGrid = this.options._sourceElement
                    .closest('[data-role="grid-sidebar-component-container"]')
                    .find(gridElement);
                if (foundGrid.length) {
                    mediator.trigger('grid-sidebar:load:' + this.options.sidebarAlias);
                }
            }
        },

        /**
         * @param {Object} data
         */
        onSidebarChange: function(data) {
            var params = _.extend(
                this._getQueryParamsFromUrl(location.search),
                this._getDatagridParams(),
                data.params
            );
            data = _.extend({reload: true, updateUrl: true}, data);
            var widgetParams = _.extend(
                _.omit(this.options.widgetRouteParameters, this.options.gridParam),
                params
            );

            if (data.updateUrl) {
                this._pushState(_.omit(params, _.isNull));
            }

            this._patchGridCollectionUrl(params);

            if (data.reload) {
                if (data.widgetReload) {
                    var self = this;
                    widgetManager.getWidgetInstanceByAlias(
                        this.options.widgetAlias,
                        function(widget) {
                            widget.setUrl(routing.generate(self.options.widgetRoute, widgetParams));
                            widget.render();
                        }
                    );
                } else {
                    this.gridCollection.getPage(1);
                }
            }
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
            if (!_.has(this.gridCollection, 'options')) {
                return params;
            }
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
