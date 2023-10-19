define(function(require) {
    'use strict';

    const BaseComponent = require('oroactivity/js/app/components/activity-context-activity-component');
    const NoteActivityContextComponentView = require('oronote/js/app/views/note-context-component-view');

    const ActivityContextComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ActivityContextComponent(options) {
            ActivityContextComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initView: function() {
            this.contextsView = new NoteActivityContextComponentView(this.getViewOptions());
        }
    });

    return ActivityContextComponent;
});
