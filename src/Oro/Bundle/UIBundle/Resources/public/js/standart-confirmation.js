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
        template: require('text!oroui/templates/standart-confirmation.html'),

        /** @property {String} */
        className: 'modal oro-modal-normal',

        /** @property {String} */
        okButtonClass: 'btn-danger',

        /** @property {Boolean} */
        allowOk: true,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            //Set custom template settings
            var interpolate = {
                interpolate: /\{\{(.+?)\}\}/g,
                evaluate: /<%([\s\S]+?)%>/g
            };

            options = _.extend({
                title: __('Confirmation'),
                okText: __('Yes'),
                cancelText: __('Cancel'),
                template: _.template(this.template, interpolate),
                allowOk: this.allowOk
            }, options);

            arguments[0] = options;
            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
