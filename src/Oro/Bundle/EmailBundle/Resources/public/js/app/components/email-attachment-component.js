/*global define*/
define(function (require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        EmailAttachmentView = require('oroemail/js/app/views/email-attachment-view');

    /**
     * @exports EmailAttachmentComponent
     */
    return BaseComponent.extend({
        attachmentsView: null,

        initialize: function(options) {
            this.options = options;
            this.init();
        },

        init: function() {
            if (this.options.dialogButton) {
                this.initDialogButton(this.options.dialogButton);
            }
            var $container = this.options._sourceElement.find('#' + this.options.container);
            this.initContainer($container);

            this.attachmentsView.render();
        },

        initDialogButton: function(dialogButton) {
            var self = this;

            var $dialogButton = $('#' + dialogButton);
            $dialogButton.click(function() {
                self.attachmentsView.add({});
            });
        },

        initContainer: function(container) {
            var items = typeof this.options.items == 'undefined' ? [] : this.options.items;
            this.attachmentsView = new EmailAttachmentView({
                items: items,
                el: container,
                inputName: this.options.inputName
            });
        }
    });
});
