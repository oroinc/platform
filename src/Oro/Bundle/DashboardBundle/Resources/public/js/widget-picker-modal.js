define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const Modal = require('oroui/js/modal');
    const widgetPickerModalTemplate = require('tpl-loader!oroui/templates/widget-picker/widget-picker-modal-template.html');

    const BaseCollection = require('oroui/js/app/models/base/collection');
    const WidgetPickerModel = require('oroui/js/app/models/widget-picker/widget-picker-model');
    const WidgetPickerComponent = require('oroui/js/app/components/widget-picker-component');

    const WidgetPickerModal = Modal.extend({
        className: 'modal oro-modal-normal widget-picker__modal  modal--fullscreen-small-device',

        defaultOptions: {
            /**
             * @property {DashboardContainer}
             */
            dashboard: null,
            content: widgetPickerModalTemplate(),
            title: __('oro.dashboard.add_dashboard_widgets.title'),
            cancelText: __('Close')
        },

        /**
         * @property {WidgetPickerComponent}
         */
        component: null,

        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerModal(options) {
            WidgetPickerModal.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.defaultOptions);

            WidgetPickerModal.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        open: function(cb) {
            WidgetPickerModal.__super__.open.call(this, cb);

            if (!this.component) {
                const widgetPickerCollection = new BaseCollection(
                    this.options.dashboard.getAvailableWidgets(),
                    {model: WidgetPickerModel}
                );
                this.component = new WidgetPickerComponent({
                    _sourceElement: this.$content,
                    collection: widgetPickerCollection,
                    loadWidget: this.loadWidget.bind(this)
                });
            }

            return this;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.component) {
                this.component.dispose();
                this.component = null;
            }

            WidgetPickerModal.__super__.dispose.call(this);
        },

        /**
         *
         * @param {WidgetPickerModel} widgetModel
         * @param {Function} afterLoadFunc
         */
        loadWidget: function(widgetModel, afterLoadFunc) {
            $.post(
                routing.generate('oro_api_post_dashboard_widget_add_widget'),
                {
                    widgetName: widgetModel.getName(),
                    dashboardId: this.options.dashboardId,
                    targetColumn: this.options.targetColumn
                },
                function(response) {
                    mediator.trigger('dashboard:widget:add', response);
                    afterLoadFunc();
                    widgetModel.increaseAddedCounter();
                },
                'json'
            );
        }
    });

    return WidgetPickerModal;
});
