define([
    'jquery',
    './base/view',
    'oroui/js/mediator',
    'oroui/js/tools/form-to-ajax-options'
], function($, BaseView, mediator, formToAjaxOptions) {
    'use strict';

    var PageView;

    PageView = BaseView.extend({
        events: {
            'submit form': 'onSubmit',
            'click.action.data-api [data-action=page-refresh]': 'onRefreshClick'
        },

        listen: {
            'page:beforeChange mediator': 'removeErrorClass',
            'page:error mediator': 'addErrorClass'
        },

        removeErrorClass: function() {
            this.$el.removeClass('error-page');
        },

        addErrorClass: function() {
            this.$el.addClass('error-page');
        },

        onSubmit: function(event) {
            var data;
            var options;

            if (event.isDefaultPrevented()) {
                return;
            }

            var $form = $(event.target);
            if ($form.data('nohash') && !$form.data('sent')) {
                $form.data('sent', true);
                return;
            }
            event.preventDefault();
            if ($form.data('sent')) {
                return;
            }

            $form.data('sent', true);

            var url = $form.attr('action');
            var method = $form.attr('method') || 'GET';

            if (url && method.toUpperCase() === 'GET') {
                data = $form.serialize();
                if (data) {
                    url += (url.indexOf('?') === -1 ? '?' : '&') + data;
                }
                mediator.execute('redirectTo', {url: url});
                $form.removeData('sent');
            } else {
                options = formToAjaxOptions($form, {
                    complete: function() {
                        $form.removeData('sent');
                    }
                });
                mediator.execute('submitPage', options);
            }
        },

        onRefreshClick: function() {
            mediator.execute('refreshPage');
        }
    });

    return PageView;
});
