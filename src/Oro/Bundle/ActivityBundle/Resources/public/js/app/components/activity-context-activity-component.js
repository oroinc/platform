define(function(require) {
    'use strict';

    var ActivityContextActivityComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ActivityContextActivityView = require('oroactivity/js/app/views/activity-context-activity-view');

    /**
     * @exports ActivityContextActivityComponent
     */
    ActivityContextActivityComponent = BaseComponent.extend({
        contextsView: null,

        /**
         * @inheritDoc
         */
        constructor: function ActivityContextActivityComponent() {
            ActivityContextActivityComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this._deferredInit();
            this.options = options;
            this.initView();
            this.listenTo(this.contextsView, 'render', this._resolveDeferredInit.bind(this));
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

    return ActivityContextActivityComponent;
});
