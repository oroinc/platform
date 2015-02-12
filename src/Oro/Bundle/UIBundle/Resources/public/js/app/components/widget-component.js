/*global define*/
define(function (require) {
    'use strict';

    var WidgetComponent,
        $ = require('jquery'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        mediator = require('oroui/js/mediator'),
        tools = require('oroui/js/tools'),
        mapWidgetModuleName = require('oroui/js/widget/map-widget-module-name');

    /**
     * @export oroui/js/app/components/widget-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.WidgetComponent
     */
    WidgetComponent = BaseComponent.extend({
        /**
         * @property {oroui.widget.AbstractWidget}
         * @constructor
         */
        widget: null,

        /**
         * @property {oroui.widget.AbstractWidget}
         */
        widgetInstance: null,

        /**
         * @property {boolean}
         */
        opened: false,

        defaults: {
            options: {}
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            if (options.initialized) {
                // widget is initialized from server, there's nothing to do
                return;
            }
            this.options = $.extend(true, {}, this.defaults, options);
            this.$element = options._sourceElement;

            if (this.$element) {
                if (!this.options.options.url) {
                    this.options.options.url = this.$element.data('url') || this.$element.attr('href');
                }
                if (this.options.createOnEvent) {
                    this._bindOpenEvent();
                } else {
                    this._deferredInit();
                    this.openWidget();
                }
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (!this.disposed && this.$element) {
                this.$element.off('.' + this.cid);
            }
            WidgetComponent.__super__.dispose.call(this);
        },

        /**
         * Bind handler to open widget event on source element if it exists
         *
         * @protected
         */
        _bindOpenEvent: function () {
            var eventName, handler;
            eventName = this.options.createOnEvent || 'click';
            handler = _.bind(function (e) {
                e.preventDefault();
                this.openWidget();
            }, this);
            this.$element.on(eventName + '.' + this.cid, handler);
        },

        /**
         * Handles open widget action to
         *  - check if widget module is loaded before open widget
         *
         * @protected
         */
        openWidget: function () {
            var widgetModuleName;
            if (!this.widget) {
                // defines module name and load the module, before open widget
                widgetModuleName = mapWidgetModuleName(this.options.type);
                tools.loadModules(widgetModuleName, function (Widget) {
                    this.widget = Widget;
                    this._openWidget();
                }, this);
            } else {
                this._openWidget();
            }
        },

        /**
         * Instantiates widget and opens (renders) it
         *
         * @protected
         */
        _openWidget: function () {
            var widget,
                Widget = this.widget,
                options = $.extend(true, {}, this.options.options);

            if (!this.options.multiple && this.opened) {
                // single instance is already opened
                return;
            }

            // Create and open widget
            widget = new Widget(options);

            this._bindEnvironmentEvent(widget);

            if (!this.options.multiple) {
                this.opened = true;
                this.listenTo(widget, 'widgetRemove', _.bind(function () {
                    this.opened = false;
                }, this));
            }

            widget.render();
            widget.listenTo(this, 'dispose', widget.dispose);

            this._resolveDeferredInit();
        },

        /**
         * Binds widget instance to environment events
         *
         * @param {oroui.widget.AbstractWidget} widget
         * @protected
         */
        _bindEnvironmentEvent: function (widget) {
            var reloadEvent = this.options['reload-event'],
                reloadGridName = this.options['reload-grid-name'],
                refreshWidgetAlias = this.options['refresh-widget-alias'],
                reloadWidgetAlias = this.options['reload-widget-alias'];

            reloadEvent = reloadEvent || 'widget_success:' + (widget.getAlias() || widget.getWid());

            if (refreshWidgetAlias) {
                widget.listenTo(mediator, reloadEvent, function () {
                    mediator.trigger('widget:doRefresh:' + refreshWidgetAlias);
                });
            }

            if (reloadWidgetAlias) {
                widget.listenTo(mediator, reloadEvent, function () {
                    mediator.execute('widgets:getByAliasAsync', reloadWidgetAlias, function (widget) {
                        widget.loadContent();
                    });
                });
            }

            if (reloadGridName) {
                widget.listenTo(mediator, reloadEvent, function () {
                    mediator.trigger('datagrid:doRefresh:' + reloadGridName);
                });
            }
        }
    });

    return WidgetComponent;
});
