define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const widgetPickerModalTemplate = require('text-loader!oroui/templates/widget-picker/widget-picker-modal-template.html');
    const WidgetContainerModel = require('orosidebar/js/app/models/sidebar-widget-container-model');
    const BaseCollection = require('oroui/js/app/models/base/collection');
    const WidgetPickerModel = require('oroui/js/app/models/widget-picker/widget-picker-model');
    const WidgetPickerComponent = require('oroui/js/app/components/widget-picker-component');
    const ModalView = require('oroui/js/modal');
    const constants = require('orosidebar/js/sidebar-constants');

    const __ = require('orotranslation/js/translator');

    const WidgetPickerModalView = ModalView.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal widget-picker__modal',

        component: null,

        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerModalView(options) {
            WidgetPickerModalView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
         * @inheritdoc
         */
        open: function(cb) {
            WidgetPickerModalView.__super__.open.call(this, cb);
            const widgetPickerCollection = new BaseCollection(
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
         * @inheritdoc
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
            const position = this.sidebarPosition;
            const widgetCollection = this.widgetCollection;
            const widgetData = widgetPickerModel.getData();
            let placement = null;
            if (position === constants.SIDEBAR_LEFT) {
                placement = 'left';
            } else if (position === constants.SIDEBAR_RIGHT) {
                placement = 'right';
            }
            const widget = new WidgetContainerModel(_.extend({}, widgetData, {
                position: widgetCollection.length,
                placement: placement
            }), {collection: widgetCollection});

            $.when(widget.save(), widget.loadModule())
                .then(function() {
                    widgetCollection.push(widget);
                    afterLoadFunc();
                    widgetPickerModel.increaseAddedCounter();
                    mediator.trigger('layout:reposition');
                });
        }
    });

    return WidgetPickerModalView;
});
