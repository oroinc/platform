gdefine(function(require) {
    'use strict';

    var StandardConfirmationView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var ModalView = require('oroui/js/modal');

    /**
     * Standard confirmation dialog
     *
     * @export  oroui/js/standard-confirmation
     * @class   oroui.StandardConfirmationView
     * @extends oroui.ModalView
     */
    StandardConfirmationView = ModalView.extend({

        /** @property {String} */
        className: 'modal oro-modal-normal',

        defaultOptions: {
            title: __('Confirmation'),
            okText: __('Yes'),
            cancelText: __('Cancel')
        },

        /**
         * @inheritDoc
         */
        constructor: function StandardConfirmationView() {
            StandardConfirmationView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            options = _.defaults(options, this.defaultOptions);

            StandardConfirmationView.__super__.initialize.call(this, options);
        }
    });

    return StandardConfirmationView;
});
