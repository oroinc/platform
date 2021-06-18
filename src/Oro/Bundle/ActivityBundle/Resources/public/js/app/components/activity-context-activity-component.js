define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const ActivityContextActivityView = require('oroactivity/js/app/views/activity-context-activity-view');

    /**
     * @exports ActivityContextActivityComponent
     */
    const ActivityContextActivityComponent = BaseComponent.extend({
        contextsView: null,

        /**
         * @inheritdoc
         */
        constructor: function ActivityContextActivityComponent(options) {
            ActivityContextActivityComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this._deferredInit();
            this.options = options;
            this.initView();
            this.listenTo(this.contextsView, 'render', this._resolveDeferredInit.bind(this));
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

    return ActivityContextActivityComponent;
});
