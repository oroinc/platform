define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');
    var template = require('tpl!oroui/templates/delete-confirmation.html');
    var DeleteConfirmation;

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmation
     * @extends oroui.Modal
     */
    DeleteConfirmation = Modal.extend({

        /** @property {String} */
        template: template,

        /** @property {String} */
        className: 'modal oro-modal-danger',

        /** @property {String} */
        okText: __('Yes, Delete'),

        /** @property {String} */
        title: __('Delete Confirmation'),

        /** @property {String} */
        cancelText: __('Cancel'),

        okButtonClass: 'btn ok btn-danger',

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            _.defaults(options, _.pick(DeleteConfirmation.prototype, 'title', 'okText', 'okButtonClass', 'cancelText'));
            DeleteConfirmation.__super__.initialize.call(this, options);
        }
    });

    return DeleteConfirmation;
});
