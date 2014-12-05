/*jslint nomen: true*/
/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/app/views/base/view', 'oroui/js/modal'
], function ($, _, __, BaseView, Modal) {
    'use strict';

    var GuestNotifierView = BaseView.extend({
        /**
         * @constructor
         */
        initialize: function () {
            var $form = $(this.$el).closest('form'),
                isModalShown = false,
                getFormState = function ($form) {
                    var $submit = $form.find('input[name="input_action"]');
                    $submit.attr('disabled', true);
                    // Verification Rule: Change calendar event color is not influence into notification popup
                    var $colors = $form.find('#oro_calendar_event_form_backgroundColor');
                    $colors.attr('disabled', true);
                    var result = $form.serialize();
                    $submit.attr('disabled', false);
                    $colors.attr('disabled', false);

                    return result;
                },
                formInitialState = getFormState($form),
                isChanged = function ($currentForm) {
                    // Verification Rule: Change calendar event color is not influence into notification popup
                    var $colors = $currentForm.find('#oro_calendar_event_form_backgroundColor');
                    $colors.attr('disabled', true);
                    return getFormState($currentForm) != formInitialState;
                };

            this.$parent = $form.parent();

            this.$parent.on('submit.' + this.cid, function (e) {
                if (!isModalShown && isChanged($form)) {
                    var formId = $form.attr('id'),
                        $notifyInvitedUsers = $form.find('input[name="' + formId + '[notifyInvitedUsers]"]'),
                        confirm = new Modal({
                            title: __('Notify invited users'),
                            okText: __('Notify'),
                            cancelText: __("Don't notify"),
                            content: __('All invited users will be notified of changes. Do you want to notify all invited users about changes?'),
                            className: 'modal modal-primary',
                            okButtonClass: 'btn-primary btn-large',
                            handleClose: true
                        });

                    confirm.on('ok', function () {
                        $notifyInvitedUsers.val(true);
                        $form.submit();
                        isModalShown = false;
                    });

                    confirm.on('cancel', function () {
                        $form.submit();
                        isModalShown = false;
                    });

                    confirm.on('close', function () {
                        isModalShown = false;
                    });

                    confirm.open();

                    isModalShown = true;
                    e.preventDefault();
                }
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (!this.disposed) {
                if (this.$parent) {
                    this.$parent.off('.' + this.cid);
                }
            }
            GuestNotifierView.__super__.dispose.call(this);
        }
    });

    return GuestNotifierView;
});
