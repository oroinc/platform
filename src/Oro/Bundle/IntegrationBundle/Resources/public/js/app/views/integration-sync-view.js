define(function(require) {
    'use strict';

    var IntegrationSyncView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var messenger = require('oroui/js/messenger');
    var mediator = require('oroui/js/mediator');
    var Modal = require('oroui/js/modal');

    IntegrationSyncView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['integrationName']),

        modalClass: 'modal oro-modal-danger',

        events: {
            'click .schedule-button': 'onSync'
        },

        /**
         * @inheritDoc
         */
        constructor: function IntegrationSyncView() {
            IntegrationSyncView.__super__.constructor.apply(this, arguments);
        },

        onSync: function(e) {
            e.preventDefault();

            var $currentTarget = $(e.currentTarget);
            var syncUrl = $currentTarget.data('url');

            if ($currentTarget.data('force')) {
                this._showConfirmationModal(syncUrl);
            } else {
                this._scheduleSync(syncUrl);
            }
        },

        _showConfirmationModal: function(url) {
            var confirmation = new Modal({
                title: __('oro.integration.force_sync.title'),
                okText: __('oro.integration.force_sync.ok'),
                cancelText: __('oro.integration.force_sync.cancel'),
                content: __('oro.integration.force_sync.message', {integration_name: this.integrationName}),
                className: this.modalClass,
                handleClose: true
            });

            confirmation.on('ok', function() {
                this._scheduleSync(url);
            }.bind(this));

            confirmation.open();
        },

        _scheduleSync: function(url) {
            mediator.execute('showLoading');

            $.ajax(url, {
                errorHandlerMessage: false,
                success: function(response) {
                    messenger.notificationMessage(
                        response.successful ? 'success' : 'warning',
                        response.message
                    );
                },
                error: function(response) {
                    messenger.notificationMessage(
                        'error',
                        response.responseJSON &&
                        response.responseJSON.message ? response.responseJSON.message : __('oro.integration.error')
                    );
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });
        }
    });

    return IntegrationSyncView;
});
