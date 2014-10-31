/*global define*/
define(function (require) {
    'use strict';

    var WidgetComponent,
        $ = require('jquery'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        mediator = require('oroui/js/mediator'),
        tools = require('oroui/js/tools'),
        mapWidgetModuleName = require('oroui/js/map-widget-module-name');

    /**
     * @export oroui/js/app/components/widget-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.WidgetComponent
     */
    WidgetComponent = BaseComponent.extend({
        /**
         * @type {oro.AbstractWidget}
         * @constructor
         */
        widget: null,

        /**
         * @type {boolean}
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
            this.options = _.defaults(options, this.defaults);
            this.$element = options._sourceElement;

            this._bindOpenEvent();
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
         * Bind handler to open widget event
         *
         * @protected
         */
        _bindOpenEvent: function () {
            var eventName = this.options.event || 'click',
                handler = _.bind(this._openHandler, this);
            this.$element.on(eventName + '.' + this.cid, handler);
        },

        /**
         * Handles open widget action to
         *  - check if widget module is loaded before open widget
         *
         * @param {jQuery.Event} e
         * @protected
         */
        _openHandler: function (e) {
            var widgetModuleName;
            e.preventDefault();

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

            if (!options.url) {
                options.url = this.$element.data('url') || this.$element.attr('href');
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
        },

        /**
         * Binds widget instance to environment events
         *
         * @param {oro.AbstractWidget} widget
         * @protected
         */
        _bindEnvironmentEvent: function (widget) {
            var reloadEvent = this.options['reload-event'],
                reloadGridName = this.options['reload-grid-name'],
                reloadWidgetAlias = this.options['reload-widget-alias'];

            reloadEvent = reloadEvent || 'widget_success:' + (widget.getAlias() || widget.getWid());

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
