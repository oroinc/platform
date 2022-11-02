define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const ModalView = require('oroui/js/modal');

    /**
     * Standart confirmation dialog
     *
     * @export  oroui/js/standart-confirmation
     * @class   oroui.StandartConfirmationView
     * @extends oroui.ModalView
     */
    const StandartConfirmationView = ModalView.extend({

        /** @property {String} */
        className: 'modal oro-modal-normal',

        defaultOptions: {
            title: __('Confirmation'),
            okText: __('Yes'),
            cancelText: __('Cancel')
        },

        /**
         * @inheritdoc
         */
        constructor: function StandartConfirmationView(options) {
            StandartConfirmationView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            options = _.defaults(options, this.defaultOptions);

            StandartConfirmationView.__super__.initialize.call(this, options);
        }
    });

    return StandartConfirmationView;
});
