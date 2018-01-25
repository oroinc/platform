define(function(require) {
    'use strict';

    var EntityStructuresCollection;
    var EntityCollection = require('oroentity/js/app/models/entity-collection');

    EntityStructuresCollection = EntityCollection.extend({
        ROUTE: {
            read: 'oro_rest_api_cget'
        },

        type: 'entitystructures',

        /**
         * Converts entity identifier to its class name
         *
         * @param {string} className
         * @return {string}
         */
        entityClassNameToId: function(className) {
            return className.replace(/\\/g, '_');
        },

        /**
         * Converts entity class name to its identifier
         *
         * @param {string} entityId
         * @return {string}
         */
        entityIdToClassName: function(entityId) {
            return entityId.replace(/_/g, '\\');
        },

        /**
         * Gets entity model from the collection by its class name
         *
         * @param {string} className
         * @return {EntityModel}
         */
        getEntityModelByClassName: function(className) {
            if (typeof className !== 'string' || className === '') {
                throw new Error('Parameter `className` has to be a not empty string.');
            }
            return this.get(this.entityClassNameToId(className));
        }
    });

    Object.defineProperty(EntityStructuresCollection.prototype, 'globalId', {
        get: function() {
            return this.type;
        }
    });

    return EntityStructuresCollection;
});
