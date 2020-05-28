define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const ModalView = require('oroui/js/modal');

    /**
     * Standard confirmation dialog
     *
     * @export  oroui/js/standard-confirmation
     * @class   oroui.StandardConfirmationView
     * @extends oroui.ModalView
     */
    const StandardConfirmationView = ModalView.extend({

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
        constructor: function StandardConfirmationView(options) {
            StandardConfirmationView.__super__.constructor.call(this, options);
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
