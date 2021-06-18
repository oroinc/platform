define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Chaplin = require('chaplin');
    const routing = require('routing');
    /** @type {Registry} */
    const registry = require('oroui/js/app/services/registry');
    const mediator = require('oroui/js/mediator');
    const entitySync = require('oroentity/js/app/models/entity-sync');
    const entitySerializer = require('oroentity/js/app/models/entity-serializer');
    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @class EntityModel
     * @extends BaseModel
     * @mixes {Chaplin.SyncMachine}
     */
    const EntityModel = BaseModel.extend(_.extend({}, Chaplin.SyncMachine, /** @lends EntityModel.prototype */ {
        ROUTE: {
            'create': 'oro_rest_api_list',
            'update': 'oro_rest_api_item',
            'patch': 'oro_rest_api_item',
            'delete': 'oro_rest_api_item',
            'read': 'oro_rest_api_item'
        },

        /**
         * Type of entity
         * @type {string|null}
         */
        type: null,

        /**
         * Id of entity
         * @type {string|null}
         */
        id: null,

        /**
         * @inheritdoc
         */
        defaults: function() {
            return {
                id: this.id
            };
        },

        /**
         *
         * @type {Object}
         */
        _relationships: null,

        /**
         * Data for related entities
         * @type {Array}
         */
        _included: null,

        /**
         * @type {Object}
         */
        _meta: null,

        /**
         * @inheritdoc
         */
        constructor: function EntityModel(data, options) {
            options = options || {};
            _.extend(this, _.pick(data && data.data || options, 'type', 'id'));
            if (!this.type) {
                throw new TypeError('Entity type is required for EntityModel');
            }
            if (data && data.data) {
                // assume it is raw data from JSON:API and it need to be parsed
                options.parse = true;
            }
            this._relationships = {};
            this.on('request', this.beginSync);
            this.on('sync', this.finishSync);
            this.on('error', this.unsync);
            EntityModel.__super__.constructor.call(this, data, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(data, options) {
            if (!_.isEmpty(_.omit(this.attributes, 'id'))) {
                // if initialization data contains other attributes than 'id' -- consider this model as synced
                this.markAsSynced();
            }
            EntityModel.__super__.initialize.call(this, data, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this._relationships;
            delete this._included;
            delete this._meta;
            EntityModel.__super__.dispose.call(this);
        },

        registryRelieve: function(owner) {
            registry.relieve(this, owner);
        },

        /**
         * Converts model in to an object that is used for API requests
         *
         * @param {Object} [options]
         * @return {{
         *  data: {
         *      id: string?,
         *      type: string,
         *      attributes: Object<string, string|number|boolean|null|undefined>?,
         *      relationships: Object<string, {data: EntityModel.identifier|Array<EntityModel.identifier>}>?
         *  }
         * }}
         */
        toJSON: function(options) {
            const data = {
                id: this.id,
                type: this.type,
                attributes: this.getAttributes(),
                relationships: this.getRelationshipsIdentifiers()
            };
            if (_.isObject(options) && options.fields) {
                data.attributes = _.pick(data.attributes, options.fields);
                data.relationships = _.pick(data.relationships, options.fields);
            }
            return {data: _.pick(data, _.negate(_.isEmpty))};
        },

        /**
         * Fetches only scalar attributes
         *
         * @return {Object<string, string|number|boolean|null|undefined>}
         */
        getAttributes: function() {
            return _.omit(this.attributes, ['id'].concat(_.keys(this._relationships)));
        },

        /**
         * Fetches identifiers of relationships
         *
         * @return {Object<string, {data: EntityModel.identifier|Array<EntityModel.identifier>}>}
         */
        getRelationshipsIdentifiers: function() {
            return _.mapObject(this._relationships, function(value) {
                if (value instanceof EntityModel) {
                    value = {data: value.identifier};
                } else if (value instanceof Chaplin.Collection) {
                    value = value.toJSON();
                } else {
                    value = {data: null};
                }
                return value;
            });
        },

        /**
         * Combines together entity's identifier, its attributes and relationships objects
         * (relation models and relationCollections)
         * Is used for objects tree serialization
         *
         * @return {Object}
         */
        getAttributesAndRelationships: function() {
            return _.extend(this.identifier, this.getAttributes(), _.clone(this._relationships));
        },

        /**
         * Converts model into tree object with its relationships data
         * is used for template data
         *
         * @return {Object}
         */
        serialize: function() {
            const proto = entitySerializer.serializeAttributes(this, this.getAttributesAndRelationships());
            proto.toString = this.toString.bind(this);
            return Object.create(proto);
        },

        sync: function(method, model, options) {
            return entitySync.call(this, method, model, options);
        },

        /**
         * @inheritdoc
         */
        url: function(method, params) {
            return routing.generate(this.ROUTE[method], _.defaults({entity: this.type, id: this.id}, params));
        },

        /**
         * @inheritdoc
         */
        parse: function(resp, options) {
            if (this.type !== resp.data.type) {
                throw new TypeError('Entity type mismatch, attempt to parse data of "' + resp.data.type +
                    '" type by "' + this.type + '" type entity');
            }
            if (this.id !== null && this.id !== resp.data.id) {
                throw new TypeError('Id of an entity can not be changed, attempt to set new id "' + resp.data.id +
                    '" for "' + this.globalId + '"');
            }
            if (this.id === null) {
                // entity is just created and new ID is assigned
                this.id = resp.data.id;
            }
            const attrs = _.clone(resp.data.attributes) || {};
            _.extend(attrs, {id: this.id}, _.mapObject(resp.data.relationships, function(relation) {
                return {data: _.clone(relation.data)};
            }));
            if (!_.isEmpty(resp.included)) {
                this._included = resp.included;
            }
            if (!_.isEmpty(resp.data.meta)) {
                this._meta = resp.data.meta;
            }

            return attrs;
        },

        /**
         * Entity model can not be cloned, the instance have to be acquired over registry and reused across application
         * @throws {Error}
         */
        clone: function() {
            throw new Error('Clone method of EntityModel is not implemented in matter of data consistency');
        },

        reset: function(rawData, options) {
            const attrs = this.parse(rawData, options);
            this.set(attrs, options);
            if (!_.isEmpty(_.omit(this.attributes, 'id'))) {
                this.markAsSynced();
            }
        },

        trigger: function(eventName, model, value) {
            const eventNameParts = eventName.split(':');
            const eventType = eventNameParts[0];
            const fieldName = eventNameParts[1];
            if (eventType === 'change' && !_.isUndefined(fieldName) && _.isObject(value) && 'data' in value) {
                this._initRelationships(_.object([[fieldName, value]]));
            }
            return EntityModel.__super__.trigger.call(this, eventName, model, value);
        },

        _initRelationships: function(attrs) {
            const findData = function(identifier) {
                return _.findWhere(this, identifier) || identifier;
            }.bind(this._included);

            _.each(attrs, function(value, name) {
                if (!_.isObject(value) || !('data' in value)) {
                    return;
                }
                let params;
                let relation;
                const data = value.data;

                if (_.isArray(data)) {
                    params = _.extend({data: _.map(data, findData), association: name}, this.identifier);
                    relation = mediator.execute('getEntityRelationshipCollection', params, this);
                } else if (_.isObject(data)) {
                    params = {data: findData(data)};
                    relation = EntityModel.getEntityModel(params, this);
                } else {
                    relation = null;
                }

                if (this._relationships[name] !== relation) {
                    if (this._relationships[name]) {
                        registry.relieve(this._relationships[name], this);
                    }
                    this._relationships[name] = relation;
                }
            }, this);
        },

        getRelationship: function(name, applicant) {
            const relation = this._relationships[name];
            if (relation) {
                registry.retain(relation, applicant);
            }
            return relation;
        },

        toString: function() {
            return _.result(this._meta, 'title') || '';
        }
    }), /** @lends EntityModel */ {
        /**
         * Build global ID on a base of identifier properties of passed object
         *
         * @param {{type: string, id: string}} identifier
         * @return string
         */
        globalId: function(identifier) {
            return identifier.type + '::' + identifier.id;
        },

        /**
         * Check if passed object has valid identifier properties
         *
         * @param {{type: string, id: string}} identifier
         * @return boolean
         */
        isValidIdentifier: function(identifier) {
            return Boolean(
                identifier.type && _.isString(identifier.type) &&
                identifier.id && _.isString(identifier.id)
            );
        },

        /**
         * Retrieves a EntityModel from registry by its identifier if it exists,
         * or create an instance of collection and add it to registry
         *
         * @param {Object} params
         * @param {RegistryApplicant} applicant
         * @return {EntityModel}
         */
        getEntityModel: function(params, applicant) {
            const identifier = _.pick(params.data || params, 'type', 'id');
            if (!EntityModel.isValidIdentifier(identifier)) {
                throw new TypeError('params should contain valid type and id of entity');
            }

            const globalId = EntityModel.globalId(identifier);
            let model = registry.fetch(globalId, applicant);

            const options = _.extend(_.omit(params, 'data'), identifier);
            // params.data might be a null, that's why it is checked over 'data' in params
            const rawData = 'data' in params ? {data: params.data} : null;
            if (!model) {
                model = new EntityModel(rawData, options);
                try {
                    registry.put(model, applicant);
                } catch (error) {
                    /*
                     * there's might be a case when a model has relationship on itself and an entry for it
                     * was already registered during creating relationships objects inside model's constructor
                     */
                    if (/is already registered/.test(error.message) && rawData) {
                        // drop duplicated model
                        model.dispose();
                        // retrieve entry for already registered model and update it
                        model = registry.fetch(globalId, applicant);
                        model.reset(rawData, _.extend({parse: true}, options));
                    } else {
                        throw error;
                    }
                }
            } else if (rawData) {
                model.reset(rawData, _.extend({parse: true}, options));
            }

            return model;
        }
    });

    Object.defineProperty(EntityModel.prototype, 'globalId', {
        get: function() {
            return EntityModel.globalId(this);
        }
    });

    Object.defineProperty(EntityModel.prototype, 'identifier', {
        get: function() {
            return _.pick(this, 'type', 'id');
        }
    });

    /**
     * @export oroentity/js/app/models/entity-model
     */
    return EntityModel;
});
