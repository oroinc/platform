define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const ModalView = require('oroui/js/modal');
    let config = require('module-config').default(module.id);

    config = Object.assign({}, {
        className: 'modal oro-modal-danger',
        okText: __('Yes, Delete'),
        title: __('Delete Confirmation'),
        cancelText: __('Cancel'),
        okButtonClass: 'btn btn-danger'
    }, config);

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmationView
     * @extends oroui.ModalView
     */
    const DeleteConfirmationView = ModalView.extend({
        /** @property {String} */
        className: config.className,

        /** @property {String} */
        okText: config.okText,

        /** @property {String} */
        title: config.title,

        /** @property {String} */
        cancelText: config.cancelText,

        okButtonClass: config.okButtonClass,

        _attributes: {
            role: 'alertdialog'
        },

        /**
         * @inheritdoc
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
