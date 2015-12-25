define(['underscore', 'orotranslation/js/translator', 'oroui/js/modal'
    ], function(_, __, Modal) {
    'use strict';

    /**
     * Delete confirmation dialog
     *
     * @export  oroui/js/delete-confirmation
     * @class   oroui.DeleteConfirmation
     * @extends oroui.Modal
     */
    return Modal.extend({

        /** @property {String} */
        template: '\
    <% if (title) { %>\
      <div class="modal-header">\
        <% if (allowCancel) { %>\
          <a class="close">×</a>\
        <% } %>\
        <h3>{{title}}</h3>\
      </div>\
    <% } %>\
    <div class="modal-body">{{content}}</div>\
    <div class="modal-footer">\
      <% if (allowCancel) { %>\
        <% if (cancelText) { %>\
          <a href="#" class="btn cancel">{{cancelText}}</a>\
        <% } %>\
      <% } %>\
      <% if (allowOk) { %>\
        <a href="#" class="btn ok btn-primary">{{okText}}</a>\
      <% } %>\
    </div>\
  ',
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
