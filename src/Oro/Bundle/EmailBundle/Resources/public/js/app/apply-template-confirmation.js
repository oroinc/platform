define(['underscore', 'orotranslation/js/translator', 'oroui/js/modal'
], function(_, __, Modal) {
    'use strict';

    var EmailApplyTemplateModalView;
    /**
     * Apply template confirmation dialog
     *
     * @export  oroui/js/apply-template-confirmation
     * @class   oroui.ApplyTemplateConfirmation
     * @extends oroui.Modal
     */
    EmailApplyTemplateModalView = Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-danger',

        /**
         * @inheritDoc
         */
        constructor: function EmailApplyTemplateModalView() {
            EmailApplyTemplateModalView.__super__.constructor.apply(this, arguments);
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

            arguments[0] = options;
            EmailApplyTemplateModalView.__super__.initialize.apply(this, arguments);
        }
    });

    return EmailApplyTemplateModalView;
});
