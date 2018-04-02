define(function(require) {
    'use strict';

    var WidgetComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var mapWidgetModuleName = require('oroui/js/widget/map-widget-module-name');

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
         * @property {boolean}
         */
        opened: false,

        /**
         * @property {oroui.widget.AbstractWidget}
         */
        view: null,

        defaults: {
            options: {}
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetComponent() {
            WidgetComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (options.initialized) {
                // widget is initialized from server, there's nothing to do
                return;
            }

            this.options = $.extend(true, {}, this.defaults, options);
            this.$element = options._sourceElement;
            this.previousWidgetData = {};

            if (this.$element) {
                if (!this.options.options.url) {
                    this.options.options.url = this.$element.data('url') || this.$element.attr('href');
                }
                if (this.options.createOnEvent) {
                    this._bindOpenEvent();
                } else {
                    this._deferredInit();
                    this.openWidget().done(_.bind(this._resolveDeferredInit, this));
                }
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
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
        _bindOpenEvent: function() {
            var eventName = this.options.createOnEvent;
            var handler = _.bind(function(e) {
                e.preventDefault();
                this.openWidget();
            }, this);
            this.$element.on(eventName + '.' + this.cid, handler);

            mediator.on('widget_dialog:stateChange', _.bind(function(widget, data) {
                if (this.previousWidgetData && this.previousWidgetData.id === widget.getWid()) {
                    this.previousWidgetData.open = data.state === 'minimized';
                    this.previousWidgetData.widget = widget;
                }
            }, this));
        },

        /**
         * Handles open widget action to
         *  - check if widget module is loaded before open widget
         *
         *  @return {Promise}
         */
        openWidget: function() {
            var deferredOpen = $.Deferred();
            var $element = this.$element;
            if ($element) {
                $element.addClass('widget-component-processing');
                deferredOpen.then(function() {
                    $element.removeClass('widget-component-processing');
                });
            }
            var widgetModuleName;
            if (!this.widget) {
                // defines module name and load the module, before open widget
                widgetModuleName = mapWidgetModuleName(this.options.type);
                tools.loadModules(widgetModuleName, function(Widget) {
                    if (this.disposed) {
                        return;
                    }
                    this.widget = Widget;
                    this._openWidget(deferredOpen);
                }, this);
            } else {
                this._openWidget(deferredOpen);
            }
            return deferredOpen.promise();
        },

        /**
         * Instantiates widget and opens (renders) it
         *
         * @param {jQuery.Deferred} deferredOpen to handle widget opening process
         * @protected
         */
        _openWidget: function(deferredOpen) {
            var widget;
            var Widget = this.widget;
            var options = $.extend(true, {}, this.options.options);

            if (!this.options.multiple && this.previousWidgetData.open) {
                this.previousWidgetData.widget.widget.dialog('restore');
                this.previousWidgetData.open = false;
            }

            if (!this.options.multiple && this.opened) {
                // single instance is already opened
                deferredOpen.resolve();
                return;
            }

            // Create and open widget
            widget = new Widget(options);
            this.previousWidgetData.id = widget.getWid();

            this._bindEnvironmentEvent(widget);

            if (!this.options.multiple) {
                this.opened = true;
                this.listenTo(widget, 'widgetRemove', _.bind(function() {
                    this.opened = false;
                    delete this.view;
                }, this));

                if (widget.isEmbedded()) {
                    // save reference to widget (only for a single + embedded instance)
                    // to get access over named component
                    this.view = widget;
                }
            }

            widget.render();

            if (widget.isEmbedded()) {
                // if the widget is embedded, bind its life cycle with the component
                widget.listenTo(this, 'dispose', widget.dispose);
            }

            if (widget.deferredRender) {
                widget.deferredRender
                    .done(_.bind(deferredOpen.resolve, deferredOpen))
                    .fail(_.bind(deferredOpen.reject, deferredOpen));
            } else {
                deferredOpen.resolve(widget);
            }
        },

        /**
         * Binds widget instance to environment events
         *
         * @param {oroui.widget.AbstractWidget} widget
         * @protected
         */
        _bindEnvironmentEvent: function(widget) {
            var reloadEvent = this.options['reload-event'];
            var reloadGridName = this.options['reload-grid-name'];
            var refreshWidgetAlias = this.options['refresh-widget-alias'];
            var reloadWidgetAlias = this.options['reload-widget-alias'];

            reloadEvent = reloadEvent || 'widget_success:' + (widget.getAlias() || widget.getWid());

            if (refreshWidgetAlias) {
                widget.listenTo(mediator, reloadEvent, function() {
                    mediator.trigger('widget:doRefresh:' + refreshWidgetAlias);
                });
            }

            if (reloadWidgetAlias) {
                widget.listenTo(mediator, reloadEvent, function() {
                    mediator.execute('widgets:getByAliasAsync', reloadWidgetAlias, function(widget) {
                        widget.loadContent();
                    });
                });
            }

            if (reloadGridName) {
                widget.listenTo(mediator, reloadEvent, function() {
                    mediator.trigger('datagrid:doRefresh:' + reloadGridName);
                });
            }
        }
    });

    return WidgetComponent;
});
