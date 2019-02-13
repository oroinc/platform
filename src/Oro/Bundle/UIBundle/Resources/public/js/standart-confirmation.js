define(function(require) {
    'use strict';

    var StandartConfirmationView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var ModalView = require('oroui/js/modal');

    /**
     * Standart confirmation dialog
     *
     * @export  oroui/js/standart-confirmation
     * @class   oroui.StandartConfirmationView
     * @extends oroui.ModalView
     */
    StandartConfirmationView = ModalView.extend({

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
        constructor: function StandartConfirmationView() {
            StandartConfirmationView.__super__.constructor.apply(this, arguments);
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
