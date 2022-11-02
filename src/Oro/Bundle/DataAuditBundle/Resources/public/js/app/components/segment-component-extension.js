define(function(require) {
    'use strict';

    const _ = require('underscore');

    return {
        load: function(segmentComponent) {
            segmentComponent.configureFilters = _.compose(segmentComponent.configureFilters, function() {
                if (!this.conditionBuilderComponent) {
                    return;
                }
                const $condition = this.conditionBuilderComponent.view.getCriteriaOrigin('condition-data-audit');
                if ($condition.length) {
                    const collection = this.dataProvider.collection;
                    const toggleCondition = function(entityClassName) {
                        let isAvailable = false;
                        let entityModel;
                        if (entityClassName) {
                            entityModel = collection.getEntityModelByClassName(entityClassName);
                            isAvailable = _.result(entityModel.get('options'), 'auditable');
                        }
                        $condition.toggle(isAvailable);
                    };

                    toggleCondition(this.entityClassName);
                    this.on('entityChange', toggleCondition);
                }
            });
        }
    };
});
