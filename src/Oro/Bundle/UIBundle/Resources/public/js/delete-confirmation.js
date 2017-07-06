define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmation
     * @extends oroui.Modal
     */
    return Modal.extend({

        /** @property {String} */
        template: require('tpl!oroui/templates/delete-confirmation.html'),

        /** @property {String} */
        className: 'modal oro-modal-danger',

        /** @property {String} */
        okText: __('Yes, Delete'),

        /** @property {String} */
        title: __('Delete Confirmation'),

        /** @property {String} */
        cancelText: __('Cancel'),

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            options = _.extend({
                title: this.title,
                okText: this.okText,
                cancelText: this.cancelText
            }, options);

            arguments[0] = options;
            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
