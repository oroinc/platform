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
            this.initView();
        },

        initView: function() {
            var items = typeof this.options.contextTargets === 'undefined' ? false : this.options.contextTargets;
            var editable = typeof this.options.editable === 'undefined' ? false : this.options.editable;
            this.contextsView = new ActivityContextActivityView({
                contextTargets: items,
                entityId: this.options.entityId,
                el: this.options._sourceElement,
                inputName: this.options.inputName,
                target: this.options.target,
                activityClass: this.options.activityClassAlias,
                editable: editable
            });
        }
    });
});
