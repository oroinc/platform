import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import ModalView from 'oroui/js/modal';
import template from 'tpl-loader!oroui/templates/delete-confirmation.html';
import moduleConfig from 'module-config';
const config = {
    className: 'modal oro-modal-danger',
    okText: __('Yes, Delete'),
    title: __('Delete Confirmation'),
    cancelText: __('Cancel'),
    okButtonClass: 'btn btn-danger',
    allowClose: true,
    ...moduleConfig(module.id)
};

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

    template,

    /** @property {String} */
    okText: config.okText,

    /** @property {String} */
    title: config.title,

    /** @property {String} */
    cancelText: config.cancelText,

    okButtonClass: config.okButtonClass,

    allowClose: config.allowClose,

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
        const fields = ['title', 'okText', 'okButtonClass', 'cancelText', 'allowClose'];

        _.defaults(options, _.pick(DeleteConfirmationView.prototype, fields));
        DeleteConfirmationView.__super__.initialize.call(this, options);
    }
});

export default DeleteConfirmationView;
