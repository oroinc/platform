define(function(require) {
    'use strict';

    var ConditionBuilderComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ConditionBuilderView = require('oroquerydesigner/js/app/views/condition-builder/condition-builder-view');

    ConditionBuilderComponent = BaseComponent.extend({
        /**
         * @type {Array.<string>}
         */
        fieldsRelatedCriteria: null,

        /**
         * @inheritDoc
         */
        constructor: function ConditionBuilderComponent() {
            ConditionBuilderComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'fieldsRelatedCriteria'));

            var opts = _.extend({el: options._sourceElement},
                _.omit(options, 'name', '_sourceElement', '_subPromises', 'fieldsRelatedCriteria'));

            this.view = new ConditionBuilderView(opts);
            if (this.view.deferredRender) {
                this._deferredInit();
                this.view.deferredRender
                    .done(this._resolveDeferredInit.bind(this))
                    .fail(this._rejectDeferredInit.bind(this));
            }
        },

        setEntity: function(entityClassName) {
            this.view.setValue([]);
            _.each(this.fieldsRelatedCriteria, function(criteriaName) {
                var isEnabled = !_.isEmpty(entityClassName);
                this.view.toggleCriteria(criteriaName, isEnabled);
                this.view.updateCriteriaOptions(criteriaName, {rootEntity: entityClassName});
            }, this);
        }
    });

    return ConditionBuilderComponent;
});
