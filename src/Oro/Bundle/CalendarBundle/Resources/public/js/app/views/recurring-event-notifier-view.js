define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/app/views/base/view',
    'oroui/js/modal'
], function($, _, __, BaseView, Modal) {
    'use strict';

    var RecurringEventNotifierView = BaseView.extend({
        contentTemplate: null,

        /**
         * @constructor
         */
        initialize: function() {
            this.$form = this.$el.closest('form');
            this.formInitialState = this.$form.serialize();
            this.isModalShown = false;
            this.contentTemplate = _.template($('#recurring-event-notifier-content').html());

            this.$form.parent().on('submit.' + this.cid, _.bind(function(e) {
                if (!this.isModalShown && this.$form.serialize() !== this.formInitialState) {
                    this.getConfirmDialog().open();
                    this.isModalShown = true;
                    e.preventDefault();
                }
            }, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (!this.disposed) {
                if (this.$form) {
                    this.$form.parent().off('.' + this.cid);
                }
                if (this.confirmModal) {
                    this.confirmModal.dispose();
                    delete this.confirmModal;
                }
            }
            RecurringEventNotifierView.__super__.dispose.call(this);
        },

        getConfirmDialog: function() {
            if (!this.confirmModal) {
                this.confirmModal = this.createConfirmNotificationDialog();
                this.listenTo(this.confirmModal, 'ok', _.bind(function() {
                    this.isModalShown = false;
                }, this));
                this.listenTo(this.confirmModal, 'close', _.bind(function() {
                    this.isModalShown = false;
                }, this));
            }

            this.confirmModal.$el.on('click', '.all-events', _.bind(function() {
                this.confirmModal.close();
                this.$form.submit();
                this.isModalShown = false;
            }, this));

            return this.confirmModal;
        },
        createConfirmNotificationDialog: function() {
            return new Modal({
                title: __('Edit recurring event'),
                okText: __('Cancel this change'),
                okCloses: true,
                cancelText: null,
                content: this.contentTemplate(),
                className: 'modal modal-primary',
                okButtonClass: 'btn-primary btn-large',
                handleClose: true
            });
        }
    });

    return RecurringEventNotifierView;
});
