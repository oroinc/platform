define(function(require) {
    'use strict';

    var _ = require('underscore');

    return {
        load: function(segmentComponent) {
            segmentComponent.configureFilters = _.compose(segmentComponent.configureFilters, function() {
                if (!this.conditionBuilderComponent) {
                    return;
                }
                var $condition = this.conditionBuilderComponent.view.getCriteriaOrigin('condition-data-audit');
                if ($condition.length) {
                    var collection = this.dataProvider.collection;
                    var toggleCondition = function(entityClassName) {
                        var isAvailable = false;
                        var entityModel;
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
