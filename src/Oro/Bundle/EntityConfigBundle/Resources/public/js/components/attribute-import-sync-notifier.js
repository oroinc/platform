define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const sync = require('orosync/js/sync');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');

    const AttributeImportSyncNotifierComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            topic: null
        },

        /**
         * @inheritdoc
         */
        constructor: function AttributeImportSyncNotifierComponent(options) {
            AttributeImportSyncNotifierComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            sync.subscribe(this.options.topic, this.showNotification.bind(this));
            mediator.on('page:request', this.onPageChange.bind(this));
        },

        /**
         * @param {string} message
         */
        showNotification: function(message) {
            if (message.finished) {
                messenger.notificationMessage('warning', __('oro.attribute.attributes_import_has_finished'));
            }
        },

        onPageChange: function() {
            sync.unsubscribe(this.options.topic, this.showNotification.bind(this));
            mediator.off('page:request', this.onPageChange.bind(this));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.onPageChange();

            AttributeImportSyncNotifierComponent.__super__.dispose.call(this);
        }
    });

    return AttributeImportSyncNotifierComponent;
});
