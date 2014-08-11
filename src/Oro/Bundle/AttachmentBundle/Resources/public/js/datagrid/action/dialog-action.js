/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'oroui/js/mediator',
    'oro/datagrid/action/model-action',
    'oro/dialog-widget'
], function (_, __, messenger, mediator, ModelAction, DialogWidget) {
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
        widgetDefault: {
            type: 'dialog',
            multiple: false,
            'reload-grid-name': '',
            options: {
                dialogOptions: {
                    title: __('Update item'),
                    allowMaximize: false,
                    allowMinimize: false,
                    maximizedHeightDecreaseBy: 'minimize-bar',
                    width: 550
                }
            }
        },
        messagesDefault: {
            saved: __('Item updated successfully')
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Backbone.Model} options.model Optional parameter
         * @throws {TypeError} If model is undefined
         */
        initialize: function (options) {
            var widget, messages;

            DialogAction.__super__.initialize.apply(this, arguments);

            widget = this.widget
                ? _.extend(this.widgetDefault, this.widget)
                : this.widgetDefault;

            messages = this.messages
                ? _.extend(this.messagesDefault, this.messages)
                : this.messagesDefault;

            this.launcherOptions = _.extend(this.launcherOptions, {
                link: this.getLink(),
                widget: widget,
                messages: messages
            });
        },

        run: function () {
            if (!this.itemEditDialog) {
                this.itemEditDialog = new DialogWidget({
                    url: this.getLink(),
                    title: this.launcherOptions.widget.options.dialogOptions.title,
                    regionEnabled: false,
                    incrementalPosition: false,
                    dialogOptions: {
                        modal: true,
                        resizable: false,
                        width: this.launcherOptions.widget.options.dialogOptions.width,
                        close: _.bind(function () {
                            delete this.itemEditDialog;
                        }, this)
                    }
                });
                this.subviews.push(this.itemEditDialog);

                this.itemEditDialog.render();
            }
        }
    });

    return DialogAction;
});
