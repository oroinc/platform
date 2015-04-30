/*global define*/
define(function (require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component'),
        EmailContextActivityView = require('oroemail/js/app/views/email-context-activity-view');

    /**
     * @exports EmailContextActivityComponent
     */
    return BaseComponent.extend({
        contextsView: null,

        initialize: function(options) {
            this.options = options;
            this.init();
        },

        init: function() {
            this.initView();
            this.contextsView.render();
        },

        initView: function() {
            var $container = this.options._sourceElement.find('#' + this.options.container);
            var items = typeof this.options.contextTargets == 'undefined' ? [] : this.options.contextTargets;
            this.contextsView = new EmailContextActivityView({
                contextTargets: items,
                entityId:this.options.entityId,
                el: this.options._sourceElement,
                $container: $container,
                inputName: this.options.inputName,
                target: this.options.target
            });
        }
    });
});
