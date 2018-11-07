define(function(require) {
    'use strict';

    var WidgetPickerModal;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var Modal = require('oroui/js/modal');
    var widgetPickerModalTemplate = require('text!oroui/templates/widget-picker/widget-picker-modal-template.html');

    var BaseCollection = require('oroui/js/app/models/base/collection');
    var WidgetPickerModel = require('oroui/js/app/models/widget-picker/widget-picker-model');
    var WidgetPickerComponent = require('oroui/js/app/components/widget-picker-component');

    WidgetPickerModal = Modal.extend({
        className: 'modal oro-modal-normal widget-picker__modal  modal--fullscreen-small-device',

        options: {
            /**
             * @property {DashboardContainer}
             */
            dashboard: null
        },

        /**
         * @property {WidgetPickerComponent}
         */
        component: null,

        /**
         * @inheritDoc
         */
        constructor: function WidgetPickerModal() {
            WidgetPickerModal.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            options.content = _.template(widgetPickerModalTemplate)({});
            options.title = __('oro.dashboard.add_dashboard_widgets.title');
            options.cancelText = __('Close');
            Modal.prototype.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        open: function(cb) {
            Modal.prototype.open.apply(this, arguments);
            var widgetPickerCollection = new BaseCollection(
                this.options.dashboard.getAvailableWidgets(),
                {model: WidgetPickerModel}
            );
            this.component = new WidgetPickerComponent({
                _sourceElement: this.$content,
                collection: widgetPickerCollection,
                loadWidget: _.bind(this.loadWidget, this)
            });
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
