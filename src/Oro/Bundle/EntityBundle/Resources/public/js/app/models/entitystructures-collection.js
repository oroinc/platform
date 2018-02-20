define(function(require) {
    'use strict';

    var EntityStructuresCollection;
    var _ = require('underscore');
    var SyncMachineProxyCache = require('oroui/js/app/models/sync-machine-proxy-cache');
    var EntityCollection = require('oroentity/js/app/models/entity-collection');

    EntityStructuresCollection = EntityCollection.extend(/** @lends EntityStructuresCollection.prototype */{
        ROUTE: {
            read: 'oro_rest_api_list'
        },

        type: 'entitystructures',

        /**
         * @inheritDoc
         */
        constructor: function EntityStructuresCollection(data, options) {
            EntityStructuresCollection.__super__.constructor.call(this, data, options);
        },

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

    Object.defineProperties(EntityStructuresCollection.prototype, {
        globalId: {
            get: function() {
                return this.type;
            }
        },
        SYNC_MACHINE_PROXY_CACHE_STORAGE_KEY: {value: 'entitystructures_data'},
        SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME: {value: 1000 * 60 * 60 * 24}
    });

    _.extend(EntityStructuresCollection.prototype, SyncMachineProxyCache);

    return EntityStructuresCollection;
});
