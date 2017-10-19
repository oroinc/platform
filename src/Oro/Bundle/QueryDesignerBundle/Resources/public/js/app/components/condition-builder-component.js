define(function(require) {
    'use strict';

    var ConditionBuilderComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ConditionBuilderView = require('oroquerydesigner/js/app/views/condition-builder/condition-builder-view');

    ConditionBuilderComponent = BaseComponent.extend({
        defaults: {
            fieldsRelatedCriteria: []
        },

        initialize: function(options) {
            var opts = _.extend({el: options._sourceElement},
                _.omit(options, 'name', '_sourceElement', '_subPromises',
                    'fieldsRelatedCriteria', 'entityChoiceSelector'));

            this.view = new ConditionBuilderView(opts);
            if (this.view.deferredRender) {
                this._deferredInit();
                this.view.deferredRender
                    .done(this._resolveDeferredInit.bind(this))
                    .fail(this._rejectDeferredInit.bind(this));
            }

            _.extend(this, this.defaults, _.pick(options, _.keys(this.defaults)));
            if (options.entityChoiceSelector) {
                $(options.entityChoiceSelector)
                    .on('fieldsloaderupdate', this.handleEntityChange.bind(this));
            }
        },

        handleEntityChange: function(e, fields) {
            this.view.setValue([]);
            var isEnabled = !_.isEmpty(fields);
            _.each(this.fieldsRelatedCriteria, function(criteriaName) {
                this.view.toggleCriteria(criteriaName, isEnabled);
            }, this);
        }
    });

    return ConditionBuilderComponent;
});
