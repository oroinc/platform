/*global define*/
define(function (require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        EmailContextView = require('oroemail/js/app/views/email-context-view');

    /**
     * @exports EmailContextComponent
     */
    return BaseComponent.extend({
        contextView: null,

        initialize: function(options) {
            this.options = options;
            this.init();
        },

        init: function() {
            //if (this.options.dialogButton) {
            //    this.initDialogButton(this.options.dialogButton);
            //}
            this.initView();
            this.contextView.render();
        },

        //initDialogButton: function(dialogButton) {
        //    var self = this;
        //
        //    var $dialogButton = $('#' + dialogButton);
        //    $dialogButton.click(function() {
        //        self.attachmentsView.add({});
        //    });
        //},

        initView: function() {
            //var $container = this.options._sourceElement.find('#' + this.options.container);
            var items = typeof this.options.items == 'undefined' ? [] : this.options.items;
            this.contextView = new EmailContextView({
                items: items,
                el: this.options._sourceElement
                //$container: $container,
                //inputName: this.options.inputName
            });
        }
    });
});