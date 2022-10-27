define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const messenger = require('oroui/js/messenger');
    const mediator = require('oroui/js/mediator');
    const Modal = require('oroui/js/modal');

    const IntegrationSyncView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['integrationName']),

        modalClass: 'modal oro-modal-danger',

        events: {
            'click .schedule-button': 'onSync'
        },

        /**
         * @inheritdoc
         */
        constructor: function IntegrationSyncView(options) {
            IntegrationSyncView.__super__.constructor.call(this, options);
        },

        onSync: function(e) {
            e.preventDefault();

            const $currentTarget = $(e.currentTarget);
            const syncUrl = $currentTarget.data('url');

            if ($currentTarget.data('force')) {
                this._showConfirmationModal(syncUrl);
            } else {
                this._scheduleSync(syncUrl);
            }
        },

        _showConfirmationModal: function(url) {
            const confirmation = new Modal({
                title: __('oro.integration.force_sync.title'),
                okText: __('oro.integration.force_sync.ok'),
                cancelText: __('oro.integration.force_sync.cancel'),
                content: __('oro.integration.force_sync.message', {integration_name: this.integrationName}),
                className: this.modalClass,
                attributes: {
                    role: 'alertdialog'
                }
            });

            confirmation.on('ok', function() {
                this._scheduleSync(url);
            }.bind(this));

            confirmation.open();
        },

        _scheduleSync: function(url) {
            mediator.execute('showLoading');

            $.ajax(url, {
                method: 'POST',
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
