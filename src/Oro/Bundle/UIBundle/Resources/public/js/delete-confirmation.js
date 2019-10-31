define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const ModalView = require('oroui/js/modal');

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmationView
     * @extends oroui.ModalView
     */
    const DeleteConfirmationView = ModalView.extend({
        /** @property {String} */
        className: 'modal oro-modal-danger',

        /** @property {String} */
        okText: __('Yes, Delete'),

        /** @property {String} */
        title: __('Delete Confirmation'),

        /** @property {String} */
        cancelText: __('Cancel'),

        okButtonClass: 'btn btn-danger',

        /**
         * @inheritDoc
         */
        constructor: function DeleteConfirmationView(options) {
            DeleteConfirmationView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            const fields = ['title', 'okText', 'okButtonClass', 'cancelText'];

            _.defaults(options, _.pick(DeleteConfirmationView.prototype, fields));
            DeleteConfirmationView.__super__.initialize.call(this, options);
        }
    });

    return DeleteConfirmationView;
});
