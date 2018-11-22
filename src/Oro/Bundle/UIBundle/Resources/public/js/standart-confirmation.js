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
            options = _.extend({
                title: __('Confirmation'),
                okText: __('Yes'),
                cancelText: __('Cancel')
            }, options);

            arguments[0] = options;
            ModalView.prototype.initialize.apply(this, arguments);
        }
    });

    return StandartConfirmationView;
});
