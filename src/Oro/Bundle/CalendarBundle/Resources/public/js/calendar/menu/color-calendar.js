/*jslint nomen:true*/
/*global define, console*/
define(['jquery', 'underscore', 'oroui/js/app/views/base/view', 'orotranslation/js/translator', 'oroui/js/messenger',
    'jquery.simplecolorpicker'
], function ($, _, BaseView, __, messenger) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/color-calendar
     * @class   orocalendar.calendar.menu.ColorCalendar
     * @extends oroui/js/app/views/base/view
     */
    return BaseView.extend({
        initialize: function (options) {
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
            this._actionSyncObject = options._actionSyncObject;
            this.model = options.model;
            var colors = _.map(this.colorManager.colors, function (value) {
                return {'id': '#' + value, 'text': '#' + value};
            });
            options.$el.find('#calendar-color-picker').simplecolorpicker({theme: 'fontawesome', data: colors});
            if (this.model.get('backgroundColor')) {
                options.$el.find('#calendar-color-picker').simplecolorpicker('selectColor', '#' + this.model.get('backgroundColor'));
            }
            options.$el.find('#calendar-color-picker').one('change', _.bind(function(e) {
                this.onChangePicker(e.currentTarget.value);
            }, this));
        },

        onChangePicker: function (color) {
            var savingMsg = messenger.notificationMessage('warning', __('Updating the calendar, please wait ...'));
            try {
                this.model.save('backgroundColor', color.substring(1), {
                    wait: true,
                    success: _.bind(function () {
                        savingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was updated.'));
                        this.colorManager.setCalendarColors(this.model.get('calendarUid'), this.model.get('backgroundColor'));
                        this.changeVisibleButton(this.model);
                        this.connectionsView.trigger('connectionAdd', this.model);
                        if (this._actionSyncObject) {
                            this._actionSyncObject.resolve();
                        }
                    }, this),
                    error: _.bind(function (model, response) {
                        savingMsg.close();
                        this._showError(__('Sorry, the calendar updating was failed'), response.responseJSON || {});
                        if (this._actionSyncObject) {
                            this._actionSyncObject.reject();
                        }
                    }, this)
                });
            } catch (err) {
                savingMsg.close();
                this._showError(__('Sorry, unexpected error was occurred'), err);
                if (this._actionSyncObject) {
                    this._actionSyncObject.reject();
                }
            }
        },

        changeVisibleButton: function(model) {
            if (model.get('visible')) {
                var $connection = this.connectionsView.findItem(model),
                    $visibilityButton = $connection.find(this.connectionsView.selectors.visibilityButton);
                this.connectionsView._setItemVisibility($visibilityButton, model.get('backgroundColor'));
            }
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        }
    });
});
