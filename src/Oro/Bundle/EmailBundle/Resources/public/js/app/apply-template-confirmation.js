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
    const EmailApplyTemplateModalView = Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-danger',

        _attributes: {
            role: 'alertdialog'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailApplyTemplateModalView(options) {
            EmailApplyTemplateModalView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            options = _.extend({
                title: __('oro.email.emailtemplate.apply_template_confirmation_title'),
                okText: __('Yes, Proceed'),
                cancelText: __('Cancel')
            }, options);

            EmailApplyTemplateModalView.__super__.initialize.call(this, options);
        }
    });

    return EmailApplyTemplateModalView;
});
