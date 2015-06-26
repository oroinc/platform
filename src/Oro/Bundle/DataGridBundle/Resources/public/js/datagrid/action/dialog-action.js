/*jslint nomen:true*/
/*global define*/
define([
    'orotranslation/js/translator',
    'oro/datagrid/action/model-action',
    'oroui/js/app/components/widget-component'
], function(__, ModelAction, WidgetComponent) {
    'use strict';

    var DialogAction;

    /**
     * Dialog action
     *
     * @export  oro/datagrid/action/dialog-action
     * @class   oro.datagrid.action.DialogAction
     * @extends oro.datagrid.action.ModelAction
     */
    DialogAction = ModelAction.extend({
        useDirectLauncherLink: false,
        widgetOptions: null,
        widgetComponent: null,
        widgetDefaultOptions: {
            type: 'dialog',
            multiple: false,
            'reload-grid-name': '',
            options: {
                dialogOptions: {
                    title: __('Update item'),
                    allowMaximize: false,
                    allowMinimize: false,
                    modal: true,
                    resizable: false,
                    maximizedHeightDecreaseBy: 'minimize-bar',
                    width: 550
                }
            }
        },
        defaultMessages: {
            saved: __('Item updated successfully')
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @throws {TypeError} If model is undefined
         */
        initialize: function(options) {
            DialogAction.__super__.initialize.apply(this, arguments);

            // make own widgetOptions property from prototype
            this.widgetOptions = $.extend(true, {}, this.widgetDefaultOptions, this.widgetOptions, {
                options: {
                    url: this.getLink()
                }
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (!this.disposed) {
                return;
            }
            if (this.widgetComponent) {
                this.widgetComponent.dispose();
            }
            delete this.widgetComponent;
            DialogAction.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        run: function() {
            if (!this.widgetComponent) {
                this.widgetComponent = new WidgetComponent(this.widgetOptions);
            }
            this.widgetComponent.openWidget();
        }
    });

    return DialogAction;
});
