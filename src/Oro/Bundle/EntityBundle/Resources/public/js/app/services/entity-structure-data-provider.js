define(function(require) {
    'use strict';

    var EntityStructureDataProvider;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var EntityError = require('oroentity/js/entity-error');
    /** @type {Registry} */
    var registry = require('oroui/js/app/services/registry');
    var EntityStructuresCollection = require('oroentity/js/app/models/entitystructures-collection');
    var fieldFilterers = require('oroentity/js/app/services/entity-field-filterers');
    var BaseClass = require('oroui/js/base-class');

    /**
     * Filter function for entity fields
     *
     * @callback fieldsFilterer
     * @param {string} entityName
     * @param {Array.<Object>} entityFields
     * @return {Array.<Object>}
     */

    /**
     * @typedef {Object} FilterConfig
     * @property {Object.<string, boolean>} [config.optionsFilter] acceptable entity's and fields' options
     *  example:
     *      {auditable: true, configurable: true, unidirectional: false}
     * @property {[Object|string]} [config.exclude]
     * @property {[Object|string]} [config.include]
     * @property {fieldsFilterer} [config.fieldsFilterer]
     */

    /**
     * Field signature, base information of entity field
     *
     * @typedef {Object} FieldSignature
     * @property {string} field - name of field
     * @property {string} entity - class name of related entity, that can be assigned to the field
     * @property {string} parent_entity - class name of parent entity
     * @property {string} [type] - field type
     *      example: 'string', 'text', 'integer', 'decimal', 'float', 'percent', 'date', 'boolean' and other
     * @property {string} [relationType] - field's relation type
     *      example: 'manyToOne', 'manyToMany', 'oneToMany', 'oneToOne'
     * @property {boolean} [identifier] - flag of field is the identifier of entity
     */

    EntityStructureDataProvider = BaseClass.extend(/** @lends EntityStructureDataProvider.prototype */{
        cidPrefix: 'esdp',

        /**
         * @type {EntityStructuresCollection}
         */
        collection: null,

        /**
         * @type {EntityModel}
         */
        rootEntity: null,

        /**
         * @type {string}
         */
        rootEntityClassName: void 0,

        /**
         * Array of rule objects or strings that will be used for entries filtering
         *  examples:
         *      ['relationType'] - will exclude all entries that has 'relationType' key (means relational fields)
         *      [{type: 'date'}] - will exclude all entries that has property "type" equals to "date"
         * @type {[Object|string]}
         */
        exclude: null,

        /**
         * Format same as exclude option
         * @type {[Object|string]}
         */
        include: null,

        /**
         * List of acceptable options of entities and fields, is used for filtering
         *  example:
         *      {auditable: true, configurable: true, unidirectional: false}
         * @type {Object.<string, true>}
         */
        optionsFilter: null,

        /**
         * Same as optionsFilter, but filtered from options that require special filterer method
         * @type {Object.<string, true>}
         */
        regularOptionsFilter: null,

        /**
         * List of filterer functions are used to filter entity fields
         *
         * @type {Object.<string, Function>}
         */
        fieldFilterers: null,

        /**
         * Allow to define advances filter function for entity fields
         *
         * @type {fieldsFilterer}
         */
        fieldsFilterer: function(entityName, entityFields) {
            return entityFields;
        },

        /**
         * @inheritDoc
         * @param {Object} options
         * @param {EntityStructuresCollection} options.collection
         * @param {string} [options.rootEntity] class name of root entity
         * @param {string} [options.filterPreset] name of filter preset
         * @param {Object.<string, boolean>} [options.optionsFilter] acceptable entity's and fields' options
         *  example:
         *      {auditable: true, configurable: true, unidirectional: false}
         * @param {[Object|string]} [options.exclude]
         * @param {[Object|string]} [options.include]
         * @param {fieldsFilterer} [options.fieldsFilterer]
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'collection', 'fieldsFilterer'));
            if (!(this.collection instanceof EntityStructuresCollection)) {
                throw new TypeError('The option `collection` has to be an instance of `EntityStructuresCollection`');
            }

            this.fieldFilterers = {};
            if (options.filterPreset) {
                this.setFilterPreset(options.filterPreset);
            }
            this._configureFilter(_.pick(options, 'optionsFilter', 'exclude', 'include', 'fieldsFilterer'));

            if (options.rootEntity) {
                this.rootEntityClassName = options.rootEntity;
            }

            this.collection.ensureSync().then(this.onCollectionSync.bind(this));

            EntityStructureDataProvider.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            delete this.collection;
            delete this.fieldFilterers;
            EntityStructureDataProvider.__super__.dispose.call(this);
        },

        /**
         * Handles collection sync action and updates the provider
         */
        onCollectionSync: function() {
            var className = this.rootEntityClassName;
            if (className && (!this.rootEntity || this.rootEntity.get('className') !== className)) {
                this.setRootEntityClassName(className);
            }
        },

        /**
         * Finds EntityModel in collection by className and preserves search result in property
         *
         * @param {string} className class name of entity, like 'Oro\Bundle\UserBundle\Entity\User'
         */
        setRootEntityClassName: function(className) {
            this.rootEntityClassName = className || void 0;
            this.rootEntity = className ? this.collection.getEntityModelByClassName(className) : null;
        },

        _configureFilter: function(filterConfig) {
            if (filterConfig.optionsFilter) {
                this.setOptionsFilter(filterConfig.optionsFilter);
            }
            if (filterConfig.exclude) {
                this.setExcludeRules(filterConfig.exclude);
            }
            if (filterConfig.include) {
                this.setIncludeRules(filterConfig.include);
            }
            if (filterConfig.fieldsFilterer) {
                this.fieldsFilterer = filterConfig.fieldsFilterer;
            }
        },

        /**
         * Configure filters on the base of preset name
         *
         * @param {string} presetName
         */
        setFilterPreset: function(presetName) {
            if (!(presetName in EntityStructureDataProvider.filterPresets)) {
                throw new TypeError('Filter preset `' + presetName + '` is not defined');
            }
            this._configureFilter(EntityStructureDataProvider.filterPresets[presetName]);
        },

        /**
         * Defines options filter
         *  example:
         *     {auditable: true, configurable: true, unidirectional: false}
         *
         * @param {Object.<string, boolean>} optionsFilter
         */
        setOptionsFilter: function(optionsFilter) {
            this.optionsFilter = optionsFilter || {};
            this.regularOptionsFilter = _.omit(this.optionsFilter, 'unidirectional', 'auditable', 'relation');
            this._toggleFilterer('options', !_.isEmpty(this.regularOptionsFilter));
            this._toggleFilterer('unidirectional', 'unidirectional' in this.optionsFilter);
            this._toggleFilterer('auditable', 'auditable' in this.optionsFilter);
            this._toggleFilterer('relation', 'relation' in this.optionsFilter);
        },

        /**
         * Defines exclude rules for fields
         *  examples:
         *      ['relationType'] - will exclude all entries that has 'relationType' key (means relational fields)
         *      [{type: 'date'}] - will exclude all entries that has property "type" equals to "date"
         *
         * @param {[Object|string]} exclude
         */
        setExcludeRules: function(exclude) {
            this.exclude = exclude;
            this._toggleFilterer('exclude', !_.isEmpty(this.exclude));
        },

        /**
         * Defines include rules for fields
         *  examples:
         *      ['relationType'] - will include all entries that has 'relationType' key (means relational fields)
         *      [{type: 'date'}] - will include all entries that has property "type" equals to "date"
         *
         * @param {[Object|string]} include
         */
        setIncludeRules: function(include) {
            this.include = include;
            this._toggleFilterer('include', !_.isEmpty(this.include));
        },

        /**
         * Switches on/off filterer function
         *
         * @param {string} name
         * @param {boolean} flag
         * @protected
         */
        _toggleFilterer: function(name, flag) {
            if (flag) {
                this.fieldFilterers[name] = fieldFilterers[name];
            } else {
                delete this.fieldFilterers[name];
            }
        },

        /**
         * Return list of routes for entity pages
         * example:
         *      {name: 'oro_user_index', view: 'oro_user_view'}
         *
         * @returns {Object.<string, string>}
         */
        getEntityRoutes: function() {
            return this.rootEntity && this.rootEntity.get('routes') || {};
        },

        /**
         * Filters fields of entity by
         *  - `optionsFilter`
         *  - using `fieldsFilterer` callback function
         *  - `include`, `exclude` rules
         *
         * @param {Array.<Object>} fields
         * @param {string} entityClassName
         * @return {Array.<Object>}
         */
        filterEntityFields: function(fields, entityClassName) {
            if (!_.isEmpty(this.fieldFilterers)) {
                fields = _.filter(fields, function(field) {
                    return _.every(this.fieldFilterers, function(filterer) {
                        return filterer.call(this, field, entityClassName);
                    }, this);
                }, this);
            }

            fields = this.fieldsFilterer(entityClassName, fields);

            return fields;
        },

        /**
         * Extracts entity data from the model
         *
         * @param {EntityModel} entityModel
         * @return {Object}
         * @protected
         */
        _extractEntityData: function(entityModel) {
            var attrs = entityModel.getAttributes();
            attrs.fields = this.filterEntityFields(attrs.fields, entityModel.get('className'))
                .map(function(fieldData) {
                    fieldData = _.clone(fieldData);
                    if (fieldData.options) {
                        fieldData.options = _.clone(fieldData.options);
                    }
                    return fieldData;
                });
            if (attrs.options) {
                attrs.options = _.clone(attrs.options);
            }
            if (attrs.routes) {
                attrs.routes = _.clone(attrs.routes);
            }
            return attrs;
        },

        /**
         * Parses path-string and returns array of objects
         *
         * Field Path:
         *      account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName
         * Returns Chain:
         *  [{
         *      entity: {Object},
         *      path: "",
         *      basePath: ""
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account",
         *      basePath: "account+Oro\[...]\Account"
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account+Oro\[...]\Account::contacts",
         *      basePath: "account+Oro\[...]\Account::contacts+Oro\[...]Contact"
         *  }, {
         *      field: {Object},
         *      path: "account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName"
         *  }]
         *
         * @param {string} fieldId
         * @return {Array.<Object>}
         */
        pathToEntityChain: function(fieldId) {
            var entityModel;
            if (!this.rootEntity) {
                return [];
            }

            var chain = [{
                entity: this._extractEntityData(this.rootEntity),
                path: '',
                basePath: ''
            }];

            if (!fieldId) {
                return this.rootEntity ? chain : [];
            }

            try {
                _.each(fieldId.split('+'), function(part, i) {
                    var fieldName;
                    var entityClassName;
                    var pos;

                    if (i === 0) {
                        // first item is always just a field name
                        fieldName = part;
                    } else {
                        pos = part.indexOf('::');
                        if (pos !== -1) {
                            entityClassName = part.slice(0, pos);
                            fieldName = part.slice(pos + 2);
                        } else {
                            entityClassName = part;
                        }
                    }

                    if (entityClassName) {
                        // set entity for previous chain part
                        entityModel = this.collection.getEntityModelByClassName(entityClassName);
                        chain[i].entity = this._extractEntityData(entityModel);
                    }

                    if (fieldName) {
                        part = {
                            // take field from entity of previous chain part
                            field: _.find(chain[i].entity.fields, {name: fieldName})
                        };
                        chain.push(part);
                        part.path = this.entityChainToPath(chain);
                        if (part.field.relatedEntityName) {
                            part.basePath = part.path + '+' + part.field.relatedEntityName;
                        }
                    }
                }, this);
            } catch (e) {
                EntityStructureDataProvider.errorHandler();
                throw new EntityError('Can not build entity chain by given path "' + fieldId + '"');
            }

            return chain;
        },

        /**
         * Parses path-string and returns array of objects and trims trailing field
         *
         * Field Path:
         *      account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName
         * Returns Chain:
         *  [{
         *      entity: {Object},
         *      path: "",
         *      basePath: ""
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account",
         *      basePath: "account+Oro\[...]\Account"
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account+Oro\[...]\Account::contacts",
         *      basePath: "account+Oro\[...]\Account::contacts+Oro\[...]Contact"
         *  }]
         *
         * @param {string} fieldId
         * @return {Array.<Object>}
         */
        pathToEntityChainExcludeTrailingField: function(fieldId) {
            var chain = this.pathToEntityChain(fieldId);
            // if last item in the chain is a field -- trim it
            return _.last(chain).entity === void 0 ? chain.slice(0, -1) : chain;
        },

        /**
         * Combines path-string from array of objects
         *
         * Chain:
         *  [{
         *      entity: {Object},
         *      path: "",
         *      basePath: ""
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account",
         *      basePath: "account+Oro\[...]\Account"
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account+Oro\[...]\Account::contacts",
         *      basePath: "account+Oro\[...]\Account::contacts+Oro\[...]Contact"
         *  }, {
         *      field: {Object},
         *      path: "account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName"
         *  }]
         *
         *  Returns Field Path:
         *      account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName
         *
         * @param {Array.<Object>} chain
         * @return {string}
         */
        entityChainToPath: function(chain) {
            var path;

            try {
                chain = _.map(chain.slice(1), function(part) {
                    var result = part.field.name;
                    if (part.entity) {
                        result += '+' + part.entity.className;
                    }
                    return result;
                });
            } catch (e) {
                EntityStructureDataProvider.errorHandler();
                chain = [];
            }

            path = chain.join('::');

            return path;
        },

        /**
         * Prepares the object with field's info which can be matched for conditions
         *
         * @param {string} fieldId - Field Path, such as
         *      account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName
         * @return {FieldSignature|null}
         */
        getFieldSignature: function(fieldId) {
            var signature = null;
            var chain;
            var part;

            if (!fieldId) {
                return signature;
            }

            try {
                chain = this.pathToEntityChain(fieldId);
            } catch (e) {
                return signature;
            }

            part = _.last(chain);
            if (part && part.field) {
                signature = _.pick(part.field, 'type', 'relationType', 'identifier');
                signature.field = part.field.name;
                signature.entity = chain[chain.length - 2].entity.className;
                if (chain.length > 2) {
                    signature.parent_entity = chain[chain.length - 3].entity.className;
                }
            }

            return signature;
        },

        /**
         * Converts Field Path to Property Path
         *
         * Field Path:
         *      account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName
         * Returns Property Path:
         *      account.contacts.firstName
         *
         * @param {string} fieldId
         * @return {string}
         */
        getPropertyPathByPath: function(fieldId) {
            var fields = [];
            _.each(fieldId.split('+'), function(part, i) {
                var field;
                if (i === 0) {
                    // first item is always just a field name
                    fields.push(part);
                } else {
                    // field name can contain '::'
                    // thus cut off entity name with first entrance '::',
                    // remaining part is a field name
                    field = part.split('::').slice(1).join('::');
                    if (field) {
                        fields.push(field);
                    }
                }
            });

            return fields.join('.');
        },

        /**
         * Converts Property Path to Field Path
         *
         * Property Path:
         *      account.contacts.firstName
         * Returns Field Path:
         *      account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName
         *
         * @param {string} propertyPath
         * @return {string}
         */
        getPathByPropertyPath: function(propertyPath) {
            var parts;
            var properties = propertyPath.split('.');
            var entityModel = this.rootEntity;
            try {
                parts = _.map(properties.slice(0, properties.length - 1), function(fieldName) {
                    var part = fieldName;
                    var fieldData = _.find(entityModel.get('fields'), {name: fieldName});
                    if (fieldData.relatedEntityName) {
                        entityModel = this.collection.getEntityModelByClassName(fieldData.relatedEntityName);
                        part += '+' + fieldData.relatedEntityName;
                    }
                    return part;
                }, this);

                parts.push(properties[properties.length - 1]);
            } catch (e) {
                EntityStructureDataProvider.errorHandler();
                throw new EntityError('Can not define entity path by given property path "' + properties + '"');
            }
            return parts.join('::');
        }
    }, /** @lends EntityStructureDataProvider */{
        /**
         * @type {Object.<string, FilterConfig>}
         */
        filterPresets: {},

        /**
         * Creates instance of data provider and returns it with thepromise object
         *
         * @param {RegistryApplicant} applicant
         * @param {Object=} options
         * @param {string} [options.rootEntity] class name of root entity
         * @param {string} [options.filterPreset] name of filter preset
         * @param {Object.<string, boolean>} [options.optionsFilter] acceptable entity's and fields' options
         *  example:
         *      {auditable: true, configurable: true, unidirectional: false}
         * @param {[Object|string]} [options.exclude]
         * @param {[Object|string]} [options.include]
         * @param {fieldsFilterer} [options.fieldsFilterer]
         * @return {Promise.<EntityStructureDataProvider>}
         */
        getOwnDataContainer: function(applicant, options) {
            var collection;
            var entry = registry.getEntry(EntityStructuresCollection.prototype.globalId, applicant);
            if (entry) {
                collection = entry.instance;
            } else {
                collection = new EntityStructuresCollection();
                registry.registerInstance(collection, applicant);
            }

            var provider = new EntityStructureDataProvider(_.defaults({
                collection: collection
            }, options));
            provider.listenToOnce(applicant, 'dispose', provider.dispose);

            return collection.ensureSync().then(function() {
                return provider;
            });
        },

        /**
         * Filters passed fields by rules
         *
         * @param {Array.<Object>} fields
         * @param {[Object|string]} rules
         * @param {boolean} [include=false]
         * @return {Array}
         * @static
         */
        filterFields: function(fields, rules, include) {
            return _.filter(fields, function(field) {
                return Boolean(include) === fieldFilterers.anyRule(field, rules);
            });
        },

        /**
         * Defines shortcut for filter configurations
         *  it helps to reuse filter configurations
         *
         * @param {string} name
         * @param {FilterConfig} config
         */
        defineFilterPreset: function(name, config) {
            if (name in EntityStructureDataProvider.filterPresets) {
                throw new Error('Filter preset with `' + name + '` name already defined');
            }
            EntityStructureDataProvider.filterPresets[name] = config;
        }
    });

    EntityStructureDataProvider.errorHandler = (function() {
        var message = __('oro.entity.not_exist');
        var handler = _.bind(mediator.execute, mediator, 'showErrorMessage', message);
        return _.throttle(handler, 100, {trailing: false});
    }());

    /**
     * @export oroentity/js/app/services/entity-structure-data-provider
     */
    return EntityStructureDataProvider;
});
