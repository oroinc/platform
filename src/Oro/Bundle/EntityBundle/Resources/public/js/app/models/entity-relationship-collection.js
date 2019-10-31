define(function(require) {
    'use strict';

    const _ = require('underscore');
    const routing = require('routing');
    const registry = require('oroui/js/app/services/registry');
    const EntityCollection = require('oroentity/js/app/models/entity-collection');

    /**
     * @class EntityRelationshipCollection
     * @extends EntityCollection
     */
    const EntityRelationshipCollection = EntityCollection.extend(/** @lends EntityCollection.prototype */{
        ROUTE: {
            // adds, updates or deletes the specified entity identifier (for to-one association)
            // or a list of entity identifiers (for to-many association)
            // connected to the given entity by the given association
            // path: /api/{entity}/{id}/relationships/{association}
            'create': 'oro_rest_api_relationship',
            'update': 'oro_rest_api_relationship',
            'patch': 'oro_rest_api_relationship',
            'delete': 'oro_rest_api_relationship',
            // returns an entity (for to-one association) or a list of entities (for to-many association)
            // connected to the given entity by the given association
            // path: /api/{entity}/{id}/{association}
            'read': 'oro_rest_api_subresource'
        },

        /**
         * Id of entity
         * @type {string|null}
         */
        id: null,

        /**
         * Name of association
         * @type {string|null}
         */
        association: null,

        constructor: function EntityRelationshipCollection(data, options) {
            options = options || {};
            _.extend(this, _.pick(options, 'id', 'association'));
            if (!this.association) {
                throw new TypeError('Association name is required for EntityRelationshipCollection');
            }
            EntityRelationshipCollection.__super__.constructor.call(this, data, options);
        },

        /**
         * Converts relationship collection in to an object that is used for API requests
         *
         * @param {Object} [options]
         * @return {Object<string, {data: Array<EntityModel.identifier>}>}
         */
        toJSON: function(options) {
            const identifiers = this.map(function(model) {
                return model.identifier;
            });
            return {data: identifiers};
        },

        url: function(method, params) {
            return routing.generate(this.ROUTE[method], _.defaults({
                entity: this.type,
                id: this.id,
                association: this.association
            }, params));
        },

        save: function(options) {
            options = _.extend({parse: true}, options);
            const success = options.success;
            const error = options.error;
            const collection = this;
            options.success = function(resp) {
                const method = options.reset ? 'reset' : 'set';
                collection[method](resp, options);
                if (success) {
                    success.call(options.context, collection, resp, options);
                }
                collection.trigger('sync', collection, resp, options);
            };
            options.error = function(resp) {
                if (error) {
                    error.call(options.context, collection, resp, options);
                }
                collection.trigger('error', collection, resp, options);
            };
            return this.sync('update', this, options);
        }
    }, /** @lends EntityCollection */ {
        /**
         * Build global ID on a base of identifier properties of passed object
         *
         * @param {{type: string, id: string, association: string}} identifier
         * @return string
         */
        globalId: function(identifier) {
            return identifier.type + '::' + identifier.id + '::' + identifier.association;
        },

        /**
         * Check if passed object has valid identifier properties
         *
         * @param {{type: string, id: string, association: string}} identifier
         * @return boolean
         */
        isValidIdentifier: function(identifier) {
            return Boolean(
                identifier.type && _.isString(identifier.type) &&
                identifier.id && _.isString(identifier.id) &&
                identifier.association && _.isString(identifier.association)
            );
        },

        /**
         * Retrieves a EntityRelationshipCollection from registry by its identifier if it exists,
         * or create an instance of collection and add it to registry
         *
         * @param {Object} params
         * @param {RegistryApplicant} applicant
         * @return {EntityRelationshipCollection}
         */
        getEntityRelationshipCollection: function(params, applicant) {
            const identifier = _.pick(params, 'type', 'id', 'association');
            if (!EntityRelationshipCollection.isValidIdentifier(identifier)) {
                throw new TypeError('params should contain valid name of association, type and id of an entity');
            }

            const globalId = EntityRelationshipCollection.globalId(identifier);
            let collection = registry.fetch(globalId, applicant);

            const options = _.omit(params, 'data');
            const rawData = params.data ? {data: params.data} : null;
            if (!collection) {
                collection = new EntityRelationshipCollection(rawData, options);
                registry.put(collection, applicant);
            } else if (rawData) {
                collection.reset(rawData, _.extend({parse: true}, options));
            }

            return collection;
        }
    });

    Object.defineProperty(EntityRelationshipCollection.prototype, 'globalId', {
        get: function() {
            return EntityRelationshipCollection.globalId(this);
        }
    });

    Object.defineProperty(EntityRelationshipCollection.prototype, 'identifier', {
        get: function() {
            return _.pick(this, 'type', 'id', 'association');
        }
    });

    /**
     * @export oroentity/js/app/models/entity-relationship-collection
     */
    return EntityRelationshipCollection;
});
