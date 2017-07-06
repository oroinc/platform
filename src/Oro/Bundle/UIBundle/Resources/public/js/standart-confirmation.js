define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');

    /**
     * Standart confirmation dialog
     *
     * @export  oroui/js/standart-confirmation
     * @class   oroui.StandartConfirmation
     * @extends oroui.Modal
     */
    return Modal.extend({

        /** @property {String} */
        template: require('tpl!oroui/templates/standart-confirmation.html'),

        /** @property {String} */
        className: 'modal oro-modal-normal',

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
            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
