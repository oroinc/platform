/*global define*/
define([
    'jquery',
    './base/view',
    'oroui/js/mediator',
    'oroui/js/tools/form-to-ajax-options'
], function ($, BaseView, mediator, formToAjaxOptions) {
    'use strict';

    var PageView;

    PageView = BaseView.extend({
        events: {
            'submit form': 'onSubmit'
        },

        listen: {
            'page:beforeChange mediator': 'removeErrorClass',
            'page:error mediator': 'addErrorClass'
        },

        removeErrorClass: function () {
            this.$el.removeClass('error-page');
        },

        addErrorClass: function () {
            this.$el.addClass('error-page');
        },

        onSubmit: function (event) {
            var $form, url, method, data, options;

            if (event.isDefaultPrevented()) {
                return;
            }

            $form = $(event.target);
            if ($form.data('nohash') && !$form.data('sent')) {
                $form.data('sent', true);
                return;
            }
            event.preventDefault();
            if ($form.data('sent')) {
                return;
            }

            $form.data('sent', true);
            options = formToAjaxOptions($form, {
                complete: function () {
                    $form.removeData('sent');
                }
            });

            mediator.execute('submitPage', options);
        }
    });

    return PageView;
});
