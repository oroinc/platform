define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var ActivityContextActivityView = require('oroactivity/js/app/views/activity-context-activity-view');

    /**
     * @exports ActivityContextActivityComponent
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
            var items = typeof this.options.contextTargets === 'undefined' ? [] : this.options.contextTargets;
            this.contextsView = new ActivityContextActivityView({
                contextTargets: items,
                entityId: this.options.entityId,
                el: this.options._sourceElement,
                $container: $container,
                inputName: this.options.inputName,
                target: this.options.target
            });
        }
    });
});
