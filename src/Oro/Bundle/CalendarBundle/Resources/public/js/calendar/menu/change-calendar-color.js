/*jslint nomen:true*/
/*global define, console*/
define(['jquery', 'underscore', 'oroui/js/app/views/base/view', 'orotranslation/js/translator', 'oroui/js/messenger',
    'jquery.simplecolorpicker', 'jquery.minicolors'
    ], function ($, _, BaseView, __, messenger) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/change-calendar-color
     * @class   orocalendar.calendar.menu.ChangeCalendarColor
     * @extends oroui/js/app/views/base/view
     */
    return BaseView.extend({
        initialize: function (options) {
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
            this._actionSyncObject = options._actionSyncObject;
            this.model = options.model;
            var colors = _.map(this.colorManager.colors, function (value) {
                return {'id': value, 'text': value};
            });
            //Initialize color picker
            options.$el.find('#calendar-color-picker').simplecolorpicker({theme: 'fontawesome', data: colors});
            options.$el.find('#calendar-color-picker').one('change', _.bind(function(e) {
                options.$el.remove();
                this.onChangeColor(e.currentTarget.value);
            }, this));
            //Initialize minicolors
            var $customColor = options.$el.find('span.menu-custom-color');
            $customColor.minicolors({
                control: 'wheel',
                letterCase: 'uppercase',
                defaultValue: this.model.get('backgroundColor'),
                show: function () {
                    $(this).minicolors('value', $(this).minicolors('value'));
                    $(this).parent().find('.minicolors-picker').show();
                }
            });
            $customColor.parent().find('.minicolors-picker').hide();
            //Add buttons to minicolors
            $customColor.parent().find('.minicolors-panel').append(
                '<div class="form-actions">' +
                    '<button class="btn pull-right" data-action="ok" type="button">' + __('Ok') + '</button>' +
                    '<button class="btn pull-right" data-action="cancel" type="button">' + __('Cancel') + '</button>' +
                    '</div>'
            );
            $customColor.parent().find('button[data-action=ok]').one('click', _.bind(function (e) {
                e.preventDefault();
                $customColor.minicolors('hide');
                $('.context-menu-button').css('display', '');
                options.$el.remove();
                this.onChangeColor($customColor.minicolors('value'));
            }, this));
            $customColor.parent().find('button[data-action=cancel]').on('click', function (e) {
                e.preventDefault();
                $customColor.minicolors('hide');
            });
            $customColor.parent().find('.custom-color-link').on('click', function () {
                $customColor.minicolors('show');
                $customColor.show();
                $(this).css('margin-left', '5px');
            });
            //Define current model color
            if (this.model.get('backgroundColor') && _.where(colors, {'id': this.model.get('backgroundColor')}).length) {
                options.$el.find('#calendar-color-picker').simplecolorpicker('selectColor', this.model.get('backgroundColor'));
                $customColor.hide();
            } else {
                $customColor.attr('data-selected', true);
                $customColor.parent().find('.custom-color-link').css('margin-left', '5px');
            }
        },

        onChangeColor: function (color) {
            var savingMsg = messenger.notificationMessage('warning', __('Updating the calendar, please wait ...'));
            try {
                this.model.save('backgroundColor', color, {
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

        changeVisibleButton: function (model) {
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
