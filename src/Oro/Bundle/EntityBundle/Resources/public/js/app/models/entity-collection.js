define(function(require) {
    'use strict';

    var EntityCollection;
    var _ = require('underscore');
    var Chaplin = require('chaplin');
    var routing = require('routing');
    /** @type {Registry} */
    var registry = require('oroui/js/app/services/registry');
    var entitySync = require('oroentity/js/app/models/entity-sync');
    var EntityModel = require('oroentity/js/app/models/entity-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @class EntityCollection
     * @extends BaseCollection
     * @mixes {Chaplin.SyncMachine}
     */
    EntityCollection = BaseCollection.extend(_.extend({}, Chaplin.SyncMachine, /** @lends EntityCollection.prototype */{
        ROUTE: {
            // returns a list of entities of the given type
            // path: /api/{entity}
            'read': 'oro_rest_api_list',
            // deletes a list of entities of the given type by the given filters
            // path: /api/{entity}
            'delete': 'oro_rest_api_list'
        },

        /**
         * Type of entity
         * @type {string|null}
         */
        type: null,

        constructor: function EntityCollection(data, options) {
            options = options || {};
            _.extend(this, _.pick(options, 'type'));
            if (!this.type) {
                throw new TypeError('Entity type is required for EntityCollection');
            }
            if (data && data.data) {
                // assume it is raw data from JSON API and it need to be parsed
                options.parse = true;
            }
            this.on('reset', this.onReset);
            this.on('request', this.beginSync);
            this.on('sync', this.finishSync);
            this.on('error', this.unsync);
            EntityCollection.__super__.constructor.call(this, data, options);
        },

        initialize: function(data, options) {
            if (_.isObject(data) && 'data' in data) {
                // mark collection as synced
                this.markAsSynced();
            }
            EntityCollection.__super__.initialize.call(this, data, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            EntityCollection.__super__.dispose.call(this);
        },

        /**
         * Converts collection in to an object that is used for API requests
         *
         * @param {Object} [options]
         * @return {Object<string, {data: Array<Object>}>}
         */
        toJSON: function(options) {
            var data = this.map(function(model) {
                return model.toJSON(options).data;
            });
            return {data: data};
        },

        sync: function(method, model, options) {
            return entitySync.call(this, method, model, options);
        },

        url: function(method, params) {
            var route = this.ROUTE[method];
            if (!route) {
                throw new Error('Method `' + method + '` is not supported by the collection');
            }
            return routing.generate(route, _.defaults({
                entity: this.type
            }, params));
        },

        parse: function(resp, options) {
            return _.map(resp.data, function(item) {
                return {data: item};
            });
        },

        create: function() {
            throw new Error('EntityCollection::create is not implemented');
        },

        clone: function() {
            throw new Error('EntityCollection::clone is not implemented');
        },

        onReset: function(collection, options) {
            _.each(_.difference(options.previousModels, this.models), function(model) {
                registry.relieve(model, this);
            }, this);
        },

        save: function() {
            throw new Error('EntityCollection::save is not implemented');
        },

        /**
         * Method is overloaded to support raw data
         * @inheritDoc
         */
        modelId: function(attrs) {
            var id;
            if (_.isObject(attrs) && 'data' in attrs && _.size(attrs) === 1) {
                // assume it is a rawData for model
                id = _.result(attrs.data, 'id');
            } else {
                id = EntityCollection.__super__.modelId.call(this, attrs);
            }
            return id;
        },

        /**
         * Method is overloaded to replace model creating process
         * @inheritDoc
         */
        _prepareModel: function(attrs, options) {
            if (this._isModel(attrs)) {
                return attrs;
            }
            var params = _.defaults({data: attrs.data},
                options ? _.pick(options, 'parse', 'silent') : {});
            var model = EntityModel.getEntityModel(params, this);
            if (!model.validationError) {
                return model;
            }
            this.trigger('invalid', this, model.validationError, options);
            return false;
        },

        /**
         * Method is overloaded to relieve models from registry
         * @inheritDoc
         */
        remove: function(models, options) {
            EntityCollection.__super__.remove.call(this, models, options);
            models = _.isArray(models) ? models.slice() : [models];
            _.each(models, function(model) {
                registry.relieve(model, this);
            }, this);
            return this;
        },

        /**
         * Method is overloaded to handle model dispose event and cleanup reference
         * @inheritDoc
         */
        _onModelEvent: function(event, model, collection, options) {
            if (event === 'dispose' && !this.disposed) {
                this.remove(model, options);
            } else {
                EntityCollection.__super__._onModelEvent.call(this, event, model, collection, options);
            }
        }
    }));

    /**
     * @export oroentity/js/app/models/entity-collection
     */
    return EntityCollection;
});
