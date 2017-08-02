define(function() {
    'use strict';

    var WidgetMappingManager;

    WidgetMappingManager = {
        mappings: [],

        /**
         * @param {Object} mapping
         */
        addMapping: function(mapping) {
            this.mappings.push(mapping);
        },

        /**
         * @return {Array}
         */
        getMappings: function() {
            return this.mappings;
        }
    };

    return WidgetMappingManager;
});
