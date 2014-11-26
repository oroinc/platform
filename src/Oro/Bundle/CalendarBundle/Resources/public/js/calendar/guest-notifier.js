/*global define*/
define(['jquery', 'orotranslation/js/translator', 'oroui/js/modal'],
function ($, __, Modal) {
    'use strict';

    var isModalShown = false;

    return function ($form) {
        $form.submit(function (e) {
            if (!isModalShown) {
                var $form = $(this).closest('form'),
                    formId = $form.attr('id'),
                    $notifyInvitedUsers = $form.find('input[name="'+formId+'[notifyInvitedUsers]"]'),
                    confirm = new Modal({
                        title: __('Notify invited users'),
                        okText: __('Notify'),
                        cancelText: __("Don't notify"),
                        content: __('All invited users will be notified of changes.'),
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
    }
});
