define(function(require) {
    'use strict';

    const BaseComponent = require('oroactivity/js/app/components/activity-context-activity-component');
    const ActivityContextActivityView = require('oronote/js/app/views/note-context-component-view');

    const ActivityContextComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ActivityContextComponent(options) {
            ActivityContextComponent.__super__.constructor.call(this, options);
        },

        initView: function() {
            const items = typeof this.options.contextTargets === 'undefined' ? false : this.options.contextTargets;
            const editable = typeof this.options.editable === 'undefined' ? false : this.options.editable;
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

    return ActivityContextComponent;
});
