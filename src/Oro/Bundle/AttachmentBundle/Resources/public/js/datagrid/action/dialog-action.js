/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'oroui/js/mediator',
    'orodatagrid/js/datagrid/action/model-action',
    'oro/dialog-widget'
],
function (_, __, messenger, mediator, ModelAction, DialogWidget) {
    'use strict';

    /**
     * Dialog action
     *
     * @export  oroattachment/js/datagrid/action/dialog-action
     * @class   oroattachment.datagrid.action.DialogAction
     * @extends orodatagrid.datagrid.action.ModelAction
     */
    return ModelAction.extend({
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
            ModelAction.prototype.initialize.apply(this, arguments);

            var widget = this.widget
                ? _.extend(this.widgetDefault, this.widget)
                : this.widgetDefault;

            var messages = this.messages
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
                    'url': this.getLink(),
                    'title': this.launcherOptions['widget']['options']['dialogOptions']['title'],
                    'regionEnabled': false,
                    'incrementalPosition': false,
                    'dialogOptions': {
                        'modal': true,
                        'resizable': false,
                        'width': this.launcherOptions['widget']['options']['dialogOptions']['width'],
                        'close': _.bind(function () {
                            delete this.itemEditDialog;
                        }, this)
                    }
                });

                this.itemEditDialog.render();
            }
        }
    });
});
