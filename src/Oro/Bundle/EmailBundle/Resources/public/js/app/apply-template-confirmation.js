define(['underscore', 'orotranslation/js/translator', 'oroui/js/modal'
], function(_, __, Modal) {
    'use strict';

    /**
     * Apply template confirmation dialog
     *
     * @export  oroui/js/apply-template-confirmation
     * @class   oroui.ApplyTemplateConfirmation
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
        initialize: function(options) {
            options = _.extend({
                title: __('oro.email.emailtemplate.apply_template_confirmation_title'),
                okText: __('Yes, Proceed'),
                cancelText: __('Cancel')
            }, options);

            arguments[0] = options;
            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
