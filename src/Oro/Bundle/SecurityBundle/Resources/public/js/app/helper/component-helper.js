define(function(require) {
    'use strict';

    var _ = require('underscore');

    var ComponentHelper = {
        extractModelsFromGridCollection: function(datagrid) {
            var selectionState = datagrid.getSelectionState();
            var inSet = selectionState.inset;
            var models = [];
            if (inSet) {
                models = selectionState.selectedModels;
            } else {
                _.each(datagrid.collection.models, function(model) {
                    if (Object.keys(selectionState.selectedModels).length > 0) {
                        _.each(selectionState.selectedModels, function(selectedModel) {
                            var selectedModelMatched = false;
                            if (selectedModel.id === model.id) {
                                selectedModelMatched = true;
                            }
                            if (!selectedModelMatched) {
                                models.push(model);
                            }
                        });
                    } else {
                        models.push(model);
                    }
                });
            }

            return models;
        }
    };

    return ComponentHelper;
});
