/*global define*/
define(['jquery', 'underscore', 'backbone', 'routing', 'orotranslation/js/translator', 'oroui/js/mediator',
    'oroui/js/widget-manager', 'orodashboard/js/widget/dashboard-item', 'jquery-ui'],
    function ($, _, Backbone, routing, __, mediator, widgetManager, DashboardItemWidget) {
    'use strict';

    /**
     * @export orodashboard/js/dashboard-container
     * @class  orodashboard.DashboardContainer
     */
    var dashboardContainer = {
        /**
         * @property {Object}
         */
        widgets: {},

        /**
         * @property {Object}
         */
        options: {
            widgetIds: [],
            handle: ".dashboard-widget > .title",
            columnsSelector: '.dashboard-column',
            emptyTextSelector: '> .empty-text',
            allowEdit: false,
            placeholder: {
                element: function(currentItem) {
                    var height = $(currentItem).height();
                    return $(
                        '<div><div class="widget-placeholder" style="height: ' + height + 'px;">' +
                            __('oro.dashboard.drop_placeholder_label') +
                        '</div></div>'
                    )[0];
                },
                update: function(container, p) {
                    return;
                }
            }
        },

        /**
         * Initialize dashboard
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var self = this;
            this.options = _.extend({}, this.options, options);

            mediator.off('dashboard:widget:add', this.addToDashboard, this);
            mediator.on('dashboard:widget:add', this.addToDashboard, this);

            this.widgets = {};//reset widgets state before add
            _.each(this.options.widgetIds, function (wid) {
                widgetManager.getWidgetInstance(
                    wid,
                    function (widget) {
                        self.add(widget);
                    }
                );
            });

            this._updateEmptyTextVisibility();

            if (this.options.allowEdit) {
                $(this.options.columnsSelector)
                    .sortable({
                        handle: this.options.handle,
                        placeholder: this.options.placeholder,
                        connectWith: this.options.columnsSelector,
                        start: function() {
                            self._lockLayoutHeight();
                            self._hideEmptyText();
                        },
                        stop: function(event, ui) {
                            self._releaseLayoutHeight();
                            self.saveLayoutPosition();
                            self._updateEmptyTextVisibility();
                        }
                    });
            }
        },

        /**
         * @param {object} widgetModel
         */
        addToDashboard: function(widgetModel){
            var wid = 'dashboard-widget-' + widgetModel.widget.id;
            var containerId = 'widget-container-'+wid;
            var column = widgetModel.widget.layout_position[0] ? widgetModel.widget.layout_position[0] : 0;
            $('#dashboard-column-'+column).prepend($('<div id="' + containerId + '"></div>'));
            var state = {
                'id': widgetModel.widget.id,
                'expanded': widgetModel.widget.expanded,
                'layoutPosition': widgetModel.widget.layout_position
            };
            var widgetParams = {
                'widgetType': 'dashboard-item',
                'wid': wid,
                'url': routing.generate(widgetModel.config.route, widgetModel.config.route_parameters),
                'state': state,
                'loadingMaskEnabled': false,
                'container': '#' + containerId,
                'allowEdit': this.options.allowEdit
            };
            var widget = new DashboardItemWidget(widgetParams);
            widget.render();
            this.add(widget);

            if (this.options.allowEdit) {
                $(this.options.columnsSelector).sortable('refresh');
            }
        },

        /**
         * Save layout position
         */
        saveLayoutPosition: function() {
            var self = this;
            var data = {
                layoutPositions: {}
            };
            $(this.options.columnsSelector).each(function(columnIndex, columnElement) {
                $('> div', columnElement).each(function (widgetIndex, widgetContainer) {
                    var wid = $('.widget-content', widgetContainer).data('wid');
                    if (self.widgets[wid]) {
                        var widget = self.widgets[wid];
                        var id = widget.state.id;
                        data.layoutPositions[id] = widget.state.layoutPosition = [columnIndex, widgetIndex];
                    }
                });
            });

            $.ajax({
                url: this._getSaveLayoutPositionsUrl(),
                type: 'PUT',
                data: $.param(data)
            });
        },

        /**
         * Add dashboard widget
         *
         * @param {DashboardItemWidget} widget
         */
        add: function(widget) {
            var wid = widget.getWid();
            this.widgets[wid] = widget;
            this._updateEmptyTextVisibility();

            widget.off('removeFromDashboard', this._onRemove, this);
            widget.on('removeFromDashboard', this._onRemove, this);

            widget.off('collapse expand', this._onCollapseOrExpand, this);
            widget.on('collapse expand', this._onCollapseOrExpand, this);
        },

        /**
         * @param {HTMLElement} el
         * @param {DashboardItemWidget} widget
         * @private
         */
        _onRemove: function(el, widget) {
            var container = widget.widget.parent();
            widget.remove();
            container.remove();
            delete this.widgets[widget.getWid()];
            this._updateEmptyTextVisibility();

            $.ajax({
                url: this._getRemoveWidgetUrl(widget),
                type: 'DELETE'
            });
        },

        /**
         * @param {HTMLElement} el
         * @param {DashboardItemWidget} widget
         * @private
         */
        _onCollapseOrExpand: function(el, widget) {
            $.ajax({
                url: this._getUpdateWidgetUrl(widget),
                type: 'PUT',
                data: {isExpanded: widget.state.expanded ? 1 : 0}
            });
        },

        /**
         * @private
         */
        _lockLayoutHeight: function() {
            var maxHeight = 0;
            $(this.options.columnsSelector).css({minHeight: '0px'});
            $(this.options.columnsSelector).each(function(columnIndex, columnElement) {
                var currentHeight = $(columnElement).height();
                maxHeight = maxHeight > currentHeight ? maxHeight : currentHeight;
            });
            $(this.options.columnsSelector).css({minHeight: (maxHeight + 200) + 'px'});
        },

        /**
         * @private
         */
        _releaseLayoutHeight: function() {
            $(this.options.columnsSelector).css({minHeight: '0px'});
        },

        /**
         * @private
         */
        _hideEmptyText: function() {
            $(this.options.emptyTextSelector, this.options.columnsSelector).addClass('hidden-empty-text');
        },


        /**
         * @private
         */
        _updateEmptyTextVisibility: function() {
            var self = this;

            $(this.options.columnsSelector).each(function(columnIndex, columnElement) {
                if (self._isEmptyColumn(columnIndex)) {
                    $(self.options.emptyTextSelector, columnElement).removeClass('hidden-empty-text');
                } else {
                    $(self.options.emptyTextSelector, columnElement).addClass('hidden-empty-text');
                }
            });
        },

        /**
         * @param {integer} columnIndex
         * @returns {boolean}
         * @private
         */
        _isEmptyColumn: function(columnIndex) {
            var result = true;

            _.each(this.widgets, function (widget) {
                if (widget.state.layoutPosition[0] == columnIndex) {
                    result = false;
                }
            });

            return result;
        },

        /**
         * @returns {string}
         * @private
         */
        _getSaveLayoutPositionsUrl: function() {
            return routing.generate('oro_api_put_dashboard_widget_positions', {dashboardId: this.options.dashboardId});
        },

        /**
         * @param {DashboardItemWidget} widget
         * @returns {string}
         * @private
         */
        _getUpdateWidgetUrl: function(widget) {
            return routing.generate('oro_api_put_dashboard_widget', {
                dashboardId: this.options.dashboardId,
                widgetId: widget.state.id
            });
        },

        /**
         * @param {DashboardItemWidget} widget
         * @returns {string}
         * @private
         */
        _getRemoveWidgetUrl: function(widget) {
            return routing.generate('oro_api_delete_dashboard_widget', {
                dashboardId: this.options.dashboardId,
                widgetId: widget.state.id
            });
        }
    };

    return dashboardContainer;
});
