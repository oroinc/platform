define(function(require) {
    'use strict';

    var ProcessStatusToggleBtnView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');

    ProcessStatusToggleBtnView = BaseView.extend({
        events: {
            'click [data-role="status-toggle"]': 'onStatusToggle'
        },

        /**
         * @inheritDoc
         */
        constructor: function ProcessStatusToggleBtnView() {
            ProcessStatusToggleBtnView.__super__.constructor.apply(this, arguments);
        },

        onStatusToggle: function(e) {
            e.preventDefault();

            $.ajax({
                url: e.currentTarget.href,
                type: 'GET',
                success: function(response) {
                    if (response.message) {
                        mediator.execute('showFlashMessage', 'success', response.message, {afterReload: true});
                    }
                    mediator.execute('refreshPage');
                }
            });
        }
    });

    return ProcessStatusToggleBtnView;
});
