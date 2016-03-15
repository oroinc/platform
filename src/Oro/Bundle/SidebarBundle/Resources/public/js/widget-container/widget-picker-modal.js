
define(function(require) {
    'use strict';

    var _ = require('underscore');

    var widgetPickerModalTemplate = require('text!oroui/templates/widget-picker/widget-picker-modal-template.html');
    var WidgetContainerModel = require('./model');

    var BaseCollection = require('oroui/js/app/models/base/collection');
    var WidgetPickerModel = require('orosidebar/js/widget-container/widget-picker-model');
    var WidgetPickerComponent = require('oroui/js/app/components/widget-picker-component');

    var Modal = require('oroui/js/modal');
    var constants = require('../constants');

    var __ = require('orotranslation/js/translator');

    /**
     * @export  orosidebar/js/widget-container/widget-picker-modal
     * @class   orosidebar.widgetContainer.WidgetPickerModal
     * @extends oro.Modal
     */
    return Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal widget-picker-modal',

        options: {
            sidebar: null
        },

        component: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            options.content = _.template(widgetPickerModalTemplate)({});
            options.title = __('oro.sidebar.widget.add.dialog.title');
            Modal.prototype.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        open: function(cb) {
            Modal.prototype.open.apply(this, arguments);
            var WidgetPickerCollection = new BaseCollection(
                this.options.sidebar.getAvailableWidgets(),
                { model: WidgetPickerModel }
            );
            this.component = new WidgetPickerComponent({
                el: this.$content,
                collection: WidgetPickerCollection,
                loadWidget: _.bind(this.loadWidget, this)
            });
        },

        /**
         *
         * @param {WidgetPickerModel} widgetModel
         * @param {Function} afterLoadFunc
         */
        loadWidget: function (widgetPickerModel, afterLoadFunc) {
            var position = this.options.sidebar.getPosition();
            var widgets = this.options.sidebar.getWidgets();
            var widgetData = widgetPickerModel.getData();
            var placement = null;
            if (position === constants.SIDEBAR_LEFT) {
                placement = 'left';
            } else if (position === constants.SIDEBAR_RIGHT) {
                placement = 'right';
            }
            var widget = new WidgetContainerModel(_.extend({}, widgetData, {
                position: widgets.length,
                placement: placement
            }));
            widgets.push(widget);
            widget
                .save()
                .then(function(){
                    afterLoadFunc();
                    widgetPickerModel.increaseAddedCounter();
                });
        }
    });
});
