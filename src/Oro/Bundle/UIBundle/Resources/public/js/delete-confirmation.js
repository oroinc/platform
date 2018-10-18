define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var ModalView = require('oroui/js/modal');
    var DeleteConfirmationView;

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmationView
     * @extends oroui.ModalView
     */
    DeleteConfirmationView = ModalView.extend({
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
        constructor: function DeleteConfirmationView() {
            DeleteConfirmationView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            var fields = ['title', 'okText', 'okButtonClass', 'cancelText'];

            _.defaults(options, _.pick(DeleteConfirmationView.prototype, fields));
            DeleteConfirmationView.__super__.initialize.call(this, options);
        }
    });

    return DeleteConfirmationView;
});
