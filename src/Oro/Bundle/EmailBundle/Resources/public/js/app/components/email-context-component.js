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
            this.initView();
            this.contextView.render();
        },

        initView: function() {
            var items = typeof this.options.items == 'undefined' ? [] : this.options.items;
            var params = typeof this.options.params == 'undefined' ? [] : this.options.params;
            this.contextView = new EmailContextView({
                items: items,
                el: this.options._sourceElement,
                params: params
            });
        }
    });
});