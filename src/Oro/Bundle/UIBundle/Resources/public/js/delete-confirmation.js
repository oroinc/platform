/*global define*/
define(['underscore', 'orotranslation/js/translator', 'oroui/js/modal'
    ], function (_, __, Modal) {
    'use strict';

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmation
     * @extends oroui.Modal
     */
    return Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-danger',

        /** @property {String} */
        okButtonClass: 'btn-danger',

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            options = _.extend({
                title: __('Delete Confirmation'),
                okText: __('Yes, Delete')
            }, options);

            arguments[0] = options;
            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
