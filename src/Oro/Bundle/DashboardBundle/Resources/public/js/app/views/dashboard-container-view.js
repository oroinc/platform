define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const widgetManager = require('oroui/js/widget-manager');
    const DashboardItemWidget = require('orodashboard/js/widget/dashboard-item');
    const dashboardUtil = require('orodashboard/js/dashboard-util');
    const ConfigurationWidget = require('orodashboard/js/widget/configuration-widget');
    const WidgetPickerModal = require('orodashboard/js/widget-picker-modal');
    const contentManager = require('orosync/js/content-manager');
    require('jquery-ui/widgets/sortable');

    const DashboardContainerView = BaseView.extend({
        /**
         * @property {Object}
         */
        widgets: {},

        /**
         * @property {Object}
         */
        options: {
            widgetIds: [],
            handle: '.dashboard-widget .widget-header .sortable',
            columnsSelector: '.dashboard-column',
            emptyTextSelector: '> .empty-text',
            allowEdit: false,
            placeholder: {
                element: function(currentItem) {
                    const height = $(currentItem).height();
                    return $(
                        '<div><div class="widget-placeholder" style="height: ' + height + 'px;">' +
                            __('oro.dashboard.drop_placeholder_label') +
                        '</div></div>'
                    )[0];
                },
                update: function(container, p) {
                }
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function DashboardContainerView(options) {
            DashboardContainerView.__super__.constructor.call(this, options);
        },

        /**
         * Initialize dashboard
         *
         * @param {Object} options
         */
        initialize: function(options) {
            const self = this;
            this.options = _.extend({}, this.options, options);

            mediator.off('dashboard:widget:add', this.addToDashboard, this);
            mediator.on('dashboard:widget:add', this.addToDashboard, this);

            this.widgets = {};// reset widgets state before add
            _.each(this.options.widgetIds, function(wid) {
                widgetManager.getWidgetInstance(
                    wid,
                    function(widget) {
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

            $('.dashboard-container-wrapper .title-buttons-container .remove-button').on('removesuccess', function() {
                dashboardUtil.onDashboardRemove($(this).attr('data-id'));
            });

            $('.dashboard-widgets-add').on('click', this._onClickAddWidget.bind(this));

            DashboardContainerView.__super__.initialize.call(this, options);

            // prevents caching dashboard page, to keep it actual
            contentManager.cacheIgnore();
        },

        /**
         * @return {Array}
         */
        getAvailableWidgets: function() {
            const widgets = this.widgets;
            return _.map(this.options.availableWidgets, function(widgetObject) {
                return _.extend(widgetObject, {
                    added: _.filter(widgets, function(widget) {
                        return widget.options.widgetName === widgetObject.widgetName;
                    }).length
                });
            });
        },

        /**
         *
         * @param {Event} e
         */
        _onClickAddWidget: function(e) {
            e.preventDefault();
            const columnIndex = $(e.target).closest(this.options.columnsSelector).index();
            const targetColumn = (columnIndex === -1) ? 0 : columnIndex;
            const widgetPickerModal = new WidgetPickerModal({
                dashboard: this,
                dashboardId: this.options.dashboardId,
                targetColumn: targetColumn,
                allowOk: false
            });

            widgetPickerModal.open();
        },

        /**
         * @param {object} data
         */
        addToDashboard: function(data) {
            const wid = 'dashboard-widget-' + data.id;
            const containerId = 'widget-container-' + wid;
            const column = data.layout_position[0] ? data.layout_position[0] : 0;
            $('#dashboard-column-' + column).prepend($('<div id="' + containerId + '"></div>'));
            const state = {
                id: data.id,
                expanded: data.expanded,
                layoutPosition: data.layout_position
            };
            const widgetParams = {
                widgetType: 'dashboard-item',
                wid: wid,
                url: routing.generate(data.config.route, _.extend(data.config.route_parameters, {
                    _widgetId: state.id
                })),
                state: state,
                loadingMaskEnabled: false,
                container: '#' + containerId,
                allowEdit: this.options.allowEdit,
                showConfig: this.options.allowEdit && !_.isEmpty(data.config.configuration),
                widgetName: data.name,
                configurationDialogOptions: data.config.configuration_dialog_options
            };
            const widget = new DashboardItemWidget(widgetParams);
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
            const self = this;
            const data = {
                layoutPositions: {}
            };
            $(this.options.columnsSelector).each(function(columnIndex, columnElement) {
                $('> div', columnElement).each(function(widgetIndex, widgetContainer) {
                    const wid = $('.widget-content', widgetContainer).data('wid');
                    if (self.widgets[wid]) {
                        const widget = self.widgets[wid];
                        const id = widget.state.id;
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
            const wid = widget.getWid();
            this.widgets[wid] = widget;
            this._updateEmptyTextVisibility();

            widget.off('removeFromDashboard', this._onRemove, this);
            widget.on('removeFromDashboard', this._onRemove, this);

            widget.off('configure', this._onConfigure, this);
            widget.on('configure', this._onConfigure, this);

            widget.off('collapse expand', this._onCollapseOrExpand, this);
            widget.on('collapse expand', this._onCollapseOrExpand, this);
        },

        /**
         * @param {HTMLElement} el
         * @param {DashboardItemWidget} widget
         * @private
         */
        _onRemove: function(el, widget) {
            const container = widget.widget.parent();
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
        _onConfigure: function(el, widget) {
            const configurationWidget = new ConfigurationWidget({
                widget: widget
            });
            mediator.on('widget_success:' + configurationWidget.getWid(), widget.render, widget);
            configurationWidget.render();
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
            this._releaseLayoutHeight();
            const columns = $(this.options.columnsSelector);
            const scrollableContainer = columns.parents('.scrollable-container').first();
            const container = scrollableContainer.find('.dashboard-container');
            const padding = parseInt(container.css('paddingBottom')) + parseInt(container.css('paddingTop'));
            let maxHeight = scrollableContainer.height() - padding;

            columns.each(function(columnIndex, columnElement) {
                const currentHeight = $(columnElement).height();
                maxHeight = maxHeight > currentHeight ? maxHeight : currentHeight;
            });

            columns.css({minHeight: maxHeight + 'px'});
            columns.sortable('refresh');
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
            const self = this;

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
            let result = true;

            _.each(this.widgets, function(widget) {
                if (widget.state.layoutPosition[0] === columnIndex) {
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
    });

    return DashboardContainerView;
});
