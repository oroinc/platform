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

            mediator.on('dashboard:widget:add', function(elementHtml){
                self.addToDashboard(elementHtml);
            });

            _.each(this.options.widgetIds, function (wid) {
                widgetManager.getWidgetInstance(
                    wid,
                    function (widget) {
                        self.add(widget);
                    }
                );
            });

            this._updateLayoutView();

            mediator.on('dashboard:model:new:element', function(wid){
                widgetManager.getWidgetInstance(
                    wid,
                    function (widget) {
                        self.add(widget);
                    }
                );
            });

            $(this.options.columnsSelector)
                .sortable({
                    handle: this.options.handle,
                    placeholder: this.options.placeholder,
                    connectWith: this.options.columnsSelector,
                    start: function() {
                        self._updateLayoutView(true);
                    },
                    stop: function(event, ui) {
                        self.saveLayoutPosition();
                        self._updateLayoutView();
                    }
                });
        },

        addToDashboard: function(html){
            $('#dashboard-column-0').prepend(html);
            $(this.options.columnsSelector).sortable('refresh');
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
            this.widgets[widget.getWid()] = widget;
            this._updateLayoutView();
            widget.on('removeFromDashboard', _.bind(this._onRemove, this))
            widget.on('collapse expand', _.bind(this._onCollapseOrExpand, this))
        },

        _onRemove: function(el, widget) {
            var container = widget.widget.parent();
            widget.remove();
            container.remove();
            delete this.widgets[widget.getWid()];
            this._updateLayoutView();

            $.ajax({
                url: this._getRemoveWidgetUrl(widget),
                type: 'DELETE'
            });
        },

        _onCollapseOrExpand: function(el, widget) {
            $.ajax({
                url: this._getUpdateWidgetUrl(widget),
                type: 'PUT',
                data: {isExpanded: widget.state.expanded ? 1 : 0}
            });
        },

        _updateLayoutView: function(hide) {
            return;
            var self = this;
            if (hide) {
                $(self.options.emptyTextSelector, this.options.columnsSelector).hide();
            } else {
                $(this.options.columnsSelector).each(function(columnIndex, columnElement) {
                    if (self._isEmptyColumn(columnIndex)) {
                        $(self.options.emptyTextSelector, columnElement).show();
                    } else {
                        $(self.options.emptyTextSelector, columnElement).hide();
                    }
                });
            }
        },

        _isEmptyColumn: function(columnIndex) {
            var result = true;

            _.each(this.widgets, function (widget) {
                if (widget.state.layoutPosition[0] == columnIndex) {
                    result = false;
                }
            });

            return result;
        },

        _getSaveLayoutPositionsUrl: function() {
            return routing.generate('oro_api_positions_dashboard_widget', {dashboardId: this.options.dashboardId});
        },

        _getUpdateWidgetUrl: function(widget) {
            return routing.generate('oro_api_put_dashboard_widget', {
                dashboardId: this.options.dashboardId,
                widgetId: widget.state.id
            });
        },

        _getRemoveWidgetUrl: function(widget) {
            return routing.generate('oro_api_delete_dashboard_widget', {
                dashboardId: this.options.dashboardId,
                widgetId: widget.state.id
            });
        }
    };

    return dashboardContainer;
});
