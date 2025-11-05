import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';
import ConditionBuilderView from 'oroquerydesigner/js/app/views/condition-builder/condition-builder-view';

const ConditionBuilderComponent = BaseComponent.extend({
    /**
     * @type {Array.<string>}
     */
    fieldsRelatedCriteria: null,

    /**
     * @inheritdoc
     */
    constructor: function ConditionBuilderComponent(options) {
        ConditionBuilderComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        _.extend(this, _.pick(options, 'fieldsRelatedCriteria'));

        const opts = _.extend({el: options._sourceElement},
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
            const isEnabled = !_.isEmpty(entityClassName);
            this.view.toggleCriteria(criteriaName, isEnabled);
            this.view.updateCriteriaOptions(criteriaName, {rootEntity: entityClassName});
        }, this);
    }
});

export default ConditionBuilderComponent;
