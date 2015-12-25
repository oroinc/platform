define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmation
     * @extends oroui.Modal
     */
    return Modal.extend({

        /** @property {String} */
        template: require('text!oroui/templates/delete-confirmation.html'),

        /** @property {String} */
        className: 'modal oro-modal-danger',

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
                title: __('Delete Confirmation'),
                okText: __('Yes, Delete'),
                cancelText: __('Cancel'),
                template: _.template(this.template, interpolate),
                allowOk: this.allowOk
            }, options);

            arguments[0] = options;
            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
