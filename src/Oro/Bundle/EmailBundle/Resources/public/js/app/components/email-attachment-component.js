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
            this.initView();

            this.attachmentsView.render();
        },

        initDialogButton: function(dialogButton) {
            var self = this;

            var $dialogButton = $('#' + dialogButton);
            $dialogButton.click(function() {
                self.attachmentsView.add({});
            });
        },

        initView: function() {
            var $container = this.options._sourceElement.find('#' + this.options.container);
            $container.css('padding-top', 5);
            var items = typeof this.options.items == 'undefined' ? [] : this.options.items;
            this.attachmentsView = new EmailAttachmentView({
                items: items,
                el: this.options._sourceElement,
                $container: $container,
                inputName: this.options.inputName
            });
        }
    });
});
