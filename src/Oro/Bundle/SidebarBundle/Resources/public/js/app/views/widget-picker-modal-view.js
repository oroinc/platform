define(function(require) {
    'use strict';

    var WidgetPickerModalView;
    var $ = require('jquery');
    var _ = require('underscore');
    var widgetPickerModalTemplate = require('text!oroui/templates/widget-picker/widget-picker-modal-template.html');
    var WidgetContainerModel = require('orosidebar/js/app/models/sidebar-widget-container-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var WidgetPickerModel = require('oroui/js/app/models/widget-picker/widget-picker-model');
    var WidgetPickerComponent = require('oroui/js/app/components/widget-picker-component');
    var ModalView = require('oroui/js/modal');
    var constants = require('orosidebar/js/sidebar-constants');

    var __ = require('orotranslation/js/translator');

    WidgetPickerModalView = ModalView.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal widget-picker__modal',

        component: null,

        /**
         * @inheritDoc
         */
        constructor: function WidgetPickerModalView(options) {
            WidgetPickerModalView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'availableWidgets', 'sidebarPosition', 'widgetCollection'));
            if (!(this.widgetCollection instanceof BaseCollection)) {
                throw new Error('Required option `widgetCollection` is missing in `WidgetPickerModalView`');
            }
            options.content = _.template(widgetPickerModalTemplate)({});
            options.title = __('oro.sidebar.widget.add.dialog.title');
            options.cancelText = __('Close');
            WidgetPickerModalView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        open: function(cb) {
            WidgetPickerModalView.__super__.open.apply(this, arguments);
            var widgetPickerCollection = new BaseCollection(
                this.availableWidgets,
                {model: WidgetPickerModel}
            );
            this.component = new WidgetPickerComponent({
                _sourceElement: this.$content,
                collection: widgetPickerCollection,
                loadWidget: this.loadWidget.bind(this)
            });
        },

        /**
         * @inheritDoc
         */
        close: function() {
            this.component.dispose();
            delete this.component;
            delete this.availableWidgets;
            delete this.widgetCollection;

            WidgetPickerModalView.__super__.close.call(this);
        },

        /**
         *
         * @param {WidgetPickerModel} widgetPickerModel
         * @param {Function} afterLoadFunc
         */
        loadWidget: function(widgetPickerModel, afterLoadFunc) {
            var position = this.sidebarPosition;
            var widgetCollection = this.widgetCollection;
            var widgetData = widgetPickerModel.getData();
            var placement = null;
            if (position === constants.SIDEBAR_LEFT) {
                placement = 'left';
            } else if (position === constants.SIDEBAR_RIGHT) {
                placement = 'right';
            }
            var widget = new WidgetContainerModel(_.extend({}, widgetData, {
                position: widgetCollection.length,
                placement: placement
            }), {collection: widgetCollection});

            $.when(widget.save(), widget.loadModule())
                .then(function() {
                    widgetCollection.push(widget);
                    afterLoadFunc();
                    widgetPickerModel.increaseAddedCounter();
                });
        }
    });

    return WidgetPickerModalView;
});
