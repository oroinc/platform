import _ from 'underscore';
import EntityError from 'oroentity/js/entity-error';
import errorHandler from 'oroentity/js/app/services/entity-structure-error-handler';
import EntityTreeNode from 'oroentity/js/app/services/entity-tree-node';
/** @type {Registry} */
import registry from 'oroui/js/app/services/registry';
import EntityStructuresCollection from 'oroentity/js/app/models/entitystructures-collection';
import fieldFilterers from 'oroentity/js/app/services/entity-field-filterers';
import BaseClass from 'oroui/js/base-class';

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
 * @property {Object.<string, boolean>} [optionsFilter] acceptable entity's and fields' options
 *  example:
 *      {auditable: true, configurable: true, unidirectional: false}
 * @property {[Object|string]} [exclude]
 * @property {[Object|string]} [include]
 * @property {fieldsFilterer|null} [fieldsFilterer]
 * @property {boolean} [isRestrictiveWhitelist]
 * @property {Object.<string, Object.<string, boolean>>} [fieldsFilterWhitelist]
 * @property {Object.<string, Object.<string, boolean>>} [fieldsFilterBlacklist]
 * @property {Object.<string, Object.<string, Object>>} [fieldsDataUpdate]
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

/**
 * Error handler object used to handle EntityError in the safe methods
 *
 * @typedef {Object} ErrorHandler
 * @property {function(EntityError)} handle takes care of EntityError inside methods with "Safely" suffix
 * @property {Function} handleOutdatedDataError is used for handling error of outdated cache
 */

/**
 * Parsed field path to the chain with an extra information for each part
 * Example:
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
 * @typedef {Array.<{entity: {Object}, field: {Object}, path: {string}, basePath: {string}}>} EntityFieldChain
 */

/**
 * Identifier of field (field path), goes from root entity and consist of
 *  '`fieldName`+`entityName`::`fieldName`+`entityName`::`fieldName`'
 * Example:
 *  account+Oro\[...]\Account::contacts+Oro\[...]\Contact::firstName
 *
 * @typedef {string} fieldId
 */

/**
 * Property path of field, goes from root entity and consist of
 *  '`fieldName`.`fieldName`.`fieldName`'
 * Example:
 *  account.contacts.firstName
 *
 * @typedef {string} propertyPath
 */

const EntityStructureDataProvider = BaseClass.extend(/** @lends EntityStructureDataProvider.prototype */{
    cidPrefix: 'esdp',

    /**
     * @type {ErrorHandler}
     */
    errorHandler: errorHandler,

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
     * Flag says if only fields from whitelist has to be represented in results
     *
     * @type {boolean}
     */
    isRestrictiveWhitelist: false,

    /**
     * Whitelist of fields that has NOT to be filtered out
     *  first key is entity class name
     *  second key is field name
     *  the value is boolean flag, means the field has to be included to results
     *
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {groups: true}} - groups field of User entity
     *          has to be included to results, despite it might not pass the filters
     *
     * @type {Object.<string, Object.<string, boolean>>}
     */
    fieldsFilterWhitelist: null,

    /**
     * Blacklist of fields that HAS to be filtered out
     *  first key is entity class name
     *  second key is field name
     *  the value is boolean flag, means the field has to be excluded from results
     *
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {groups: true}} - groups field of User entity
     *          has to be excluded from results, despite it might pass the filters
     *
     * @type {Object.<string, Object.<string, boolean>>}
     */
    fieldsFilterBlacklist: null,

    /**
     * DataUpdate that has to be applied to fields of filtered results
     *
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {
     *          groups: {type: 'enum'},  // groups field of User entity will be represented as enum
     *          viewHistory: {type: 'collection', label: 'View history'} // new field will be added
     *      }}
     *
     * @type {Object.<string, Object.<string, Object>>}
     */
    fieldsDataUpdate: null,

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
     * @type {Object.<string, boolean>}
     */
    optionsFilter: null,

    /**
     * Same as optionsFilter, but filtered from options that require special filterer method
     * @type {Object.<string, boolean>}
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
     * @type {fieldsFilterer|null}
     */
    fieldsFilterer: null,

    /**
     * @inheritdoc
     */
    constructor: function EntityStructureDataProvider(options) {
        this.registry = EntityStructureDataProvider.registry;
        EntityStructureDataProvider.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
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
     * @param {boolean} [options.isRestrictiveWhitelist]
     * @param {Object.<string, Object.<string, boolean>>} [options.fieldsFilterWhitelist]
     * @param {Object.<string, Object.<string, boolean>>} [options.fieldsFilterBlacklist]
     * @param {Object.<string, Object.<string, Object>>} [options.fieldsDataUpdate]
     */
    initialize: function(options) {
        _.extend(this, _.pick(options, 'collection', 'errorHandler'));
        if (!(this.collection instanceof EntityStructuresCollection)) {
            throw new TypeError('The option `collection` has to be an instance of `EntityStructuresCollection`');
        }

        this.fieldFilterers = {};
        this._toggleFilterer('relationToAvailableEntity', true);
        if (options.filterPreset) {
            this.setFilterPreset(options.filterPreset);
        }
        this._configureFilter(_.pick(options, _.keys(EntityStructureDataProvider._filterConfigDefaults)));

        if (options.rootEntity) {
            this.rootEntityClassName = options.rootEntity;
        }

        this.collection.ensureSync().then(this.onCollectionSync.bind(this));

        EntityStructureDataProvider.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
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
        const className = this.rootEntityClassName;
        if (!this.disposed && className && (!this.rootEntity || this.rootEntity.get('className') !== className)) {
            this.setRootEntityClassName(className);
        }
    },

    /**
     * Finds EntityModel in collection by className and preserves search result in property
     *
     * @param {string} className class name of entity, like 'Oro\Bundle\UserBundle\Entity\User'
     */
    setRootEntityClassName: function(className) {
        const prevRootEntity = this.rootEntity;
        this.rootEntityClassName = className || void 0;
        this.rootEntity = className ? this.collection.getEntityModelByClassName(className) : null;
        if (prevRootEntity !== this.rootEntity) {
            this.trigger('root-entity-change', this.rootEntity);
        }
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
        _.extend(this, _.omit(filterConfig, 'optionsFilter', 'exclude', 'include'));
    },

    /**
     * Configure filters on the base of preset name
     *
     * @param {string} presetName
     */
    setFilterPreset: function(presetName) {
        if (!(presetName in EntityStructureDataProvider._filterPresets)) {
            throw new TypeError('Filter preset `' + presetName + '` is not defined');
        }
        const filterConfig = EntityStructureDataProvider._filterPresets[presetName];
        this._configureFilter(_.defaults(filterConfig, EntityStructureDataProvider._filterConfigDefaults));
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
        const specialOptions = ['exclude', 'unidirectional', 'auditable', 'relation'];
        this.regularOptionsFilter = _.omit(this.optionsFilter, specialOptions);
        this._toggleFilterer('options', !_.isEmpty(this.regularOptionsFilter));
        _.each(specialOptions, function(option) {
            this._toggleFilterer(option, option in this.optionsFilter);
        }, this);
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
        this._toggleFilterer('excludeByRules', !_.isEmpty(this.exclude));
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
        this._toggleFilterer('includeByRules', !_.isEmpty(this.include));
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
     * Filters fields of entity
     *  first check if the field in white or black list
     *  then matches field to filterers
     *
     * @param {Object} entityData
     * @param {string} entityData.className
     * @param {Array.<Object>} entityData.fields
     * @protected
     */
    _filterEntityFields: function(entityData) {
        const entityClassName = entityData.className;
        if (!_.isEmpty(this.fieldFilterers)) {
            entityData.fields = _.filter(entityData.fields, function(field) {
                return this._isWhitelistedField(entityClassName, field) ||
                    !this.isRestrictiveWhitelist &&
                    !this._isBlacklistedField(entityClassName, field) &&
                    this._matchFieldFilterers(entityClassName, field);
            }, this);
        }

        if (this.fieldsFilterer) {
            entityData.fields = this.fieldsFilterer(entityClassName, entityData.fields);
        }
    },

    /**
     * Check if the field matches all filterers functions
     *
     * @param {string} entityClassName
     * @param {Object} field
     * @return {boolean}
     * @protected
     */
    _matchFieldFilterers: function(entityClassName, field) {
        return _.every(this.fieldFilterers, function(filterer) {
            return filterer.call(this, field, entityClassName);
        }, this);
    },

    /**
     * Check if field is in the white list
     *
     * @param {string} entityClassName
     * @param {Object} field
     * @return {boolean}
     * @protected
     */
    _isWhitelistedField: function(entityClassName, field) {
        return _.result(_.result(this.fieldsFilterWhitelist, entityClassName), field.name, false);
    },

    /**
     * Check if field is in the black list
     *
     * @param {string} entityClassName
     * @param {Object} field
     * @return {boolean}
     * @protected
     */
    _isBlacklistedField: function(entityClassName, field) {
        return _.result(_.result(this.fieldsFilterBlacklist, entityClassName), field.name, false);
    },

    /**
     * Extracts entity data from the model
     *
     * @param {EntityModel} entityModel
     * @return {Object}
     * @protected
     */
    _extractEntityData: function(entityModel) {
        const entityData = entityModel.getAttributes();
        entityData.fields = entityData.fields.map(this._extractFieldData.bind(this, entityData));
        this._filterEntityFields(entityData);
        this._applyEntityFieldsUpdates(entityData);
        if (entityData.options) {
            entityData.options = _.clone(entityData.options);
        }
        if (entityData.routes) {
            entityData.routes = _.clone(entityData.routes);
        }
        return entityData;
    },

    /**
     * Extracts field data from original
     *
     * @param {Object} entityData
     * @param {Object} fieldData
     * @return {Object}
     * @protected
     */
    _extractFieldData: function(entityData, fieldData) {
        fieldData = _.clone(fieldData);
        fieldData.parentEntity = entityData;
        if (entityData.className) {
            fieldData.entityClassName = entityData.className;
        }
        if (fieldData.relationType && _.contains(['enum', 'multiEnum'], fieldData.type)) {
            // @todo, should be fixed in API
            // `enum` or `multiEnum` field has to be with empty relationType
            // same as `tag` and `dictionary` types
            fieldData.relationType = '';
        }
        Object.defineProperty(fieldData, 'relatedEntity', {
            get: _.partial(function(provider) {
                let relatedEntityModel;
                if (this.relatedEntityName && this.relationType) {
                    relatedEntityModel = provider.collection.getEntityModelByClassName(this.relatedEntityName);
                }
                return relatedEntityModel ? provider._extractEntityData(relatedEntityModel) : null;
            }, this),
            enumerable: true
        });
        if (fieldData.options) {
            fieldData.options = _.clone(fieldData.options);
        }
        return fieldData;
    },

    /**
     * Applies field data updates
     *
     * @param {Object} entityData
     * @param {string} entityData.className
     * @param {Array.<Object>} entityData.fields
     * @protected
     */
    _applyEntityFieldsUpdates: function(entityData) {
        const fields = entityData.fields;
        const fieldsDataUpdate = _.result(this.fieldsDataUpdate, entityData.className);
        if (!fieldsDataUpdate) {
            return;
        }
        _.each(fieldsDataUpdate, function(fieldUpdate, fieldName) {
            fieldUpdate = _.defaults({name: fieldName}, _.omit(fieldUpdate, 'entity', 'relatedEntity'));
            const field = _.findWhere(fields, {name: fieldName});
            if (field) {
                _.extend(field, fieldUpdate);
            } else {
                fields.push(this._extractFieldData(entityData, fieldUpdate));
            }
        }, this);
    },

    /**
     * Parses path-string and returns array of objects
     *  in case fieldId is invalid, throws EntityError with proper error message
     *
     * @param {fieldId} fieldId
     * @return {EntityFieldChain}
     * @throws {EntityError} -- in case invalid fieldId
     */
    pathToEntityChain: function(fieldId) {
        let entityModel;
        if (!this.rootEntity) {
            return [];
        }

        const chain = [{
            entity: this._extractEntityData(this.rootEntity),
            path: '',
            basePath: ''
        }];

        if (!fieldId) {
            return this.rootEntity ? chain : [];
        }

        try {
            _.each(fieldId.split('+'), function(part, i) {
                let fieldName;
                let entityClassName;
                let pos;

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
            throw new EntityError('Can not build entity chain by given path "' + fieldId + '"');
        }

        return chain;
    },

    /**
     * Parses path-string and returns array of objects
     *  in case fieldId is invalid, handles the error and returns empty chain
     *
     * @param {fieldId} fieldId
     * @return {EntityFieldChain}
     */
    pathToEntityChainSafely: function(fieldId) {
        let chain;

        try {
            chain = this.pathToEntityChain(fieldId);
        } catch (error) {
            this.errorHandler.handle(error);
        }

        return chain || [];
    },

    /**
     * Check path-string if it is valid
     *
     * @param {fieldId} fieldId
     * @return {boolean}
     */
    validatePath: function(fieldId) {
        let isValid = true;

        try {
            this.pathToEntityChain(fieldId);
        } catch (ex) {
            isValid = false;
        }

        return isValid;
    },

    /**
     * Parses path-string and returns array of objects and trims trailing field
     *  in case fieldId is invalid, throws EntityError with proper error message
     *
     * @param {fieldId} fieldId
     * @return {EntityFieldChain}
     * @throws {EntityError} -- in case invalid fieldId
     */
    pathToEntityChainExcludeTrailingField: function(fieldId) {
        const chain = this.pathToEntityChain(fieldId);
        // if last item in the chain is a field -- trim it
        return _.last(chain).entity === void 0 ? chain.slice(0, -1) : chain;
    },

    /**
     * Parses path-string and returns array of objects and trims trailing field
     *  in case fieldId is invalid, handles the error and returns empty chain
     *
     * @param {fieldId} fieldId
     * @return {EntityFieldChain}
     */
    pathToEntityChainExcludeTrailingFieldSafely: function(fieldId) {
        let chain;

        try {
            chain = this.pathToEntityChainExcludeTrailingField(fieldId);
        } catch (error) {
            this.errorHandler.handle(error);
        }

        return chain || [];
    },

    /**
     * Combines path-string from array of objects
     *  in case entity field chain is invalid, throws EntityError with proper error message
     *
     * @param {EntityFieldChain} chain
     * @return {fieldId}
     * @throws {EntityError} -- in case invalid chain
     */
    entityChainToPath: function(chain) {
        try {
            chain = _.map(chain.slice(1), function(part) {
                let result = part.field.name;
                if (part.entity) {
                    result += '+' + part.entity.className;
                }
                return result;
            });
        } catch (e) {
            throw new EntityError('Can not build field path from given chain');
        }

        const path = chain.join('::');

        return path;
    },

    /**
     * Combines path-string from array of objects
     *  in case entity field chain is invalid, handles the error and returns empty field path
     *
     * @param {EntityFieldChain} chain
     * @return {fieldId}
     */
    entityChainToPathSafely: function(chain) {
        let path;

        try {
            path = this.entityChainToPath(chain);
        } catch (error) {
            this.errorHandler.handle(error);
        }

        return path || '';
    },

    /**
     * Prepares the object with field's info which can be matched for conditions
     *  in case invalid entity field path, handles the error and returns null as field signature
     *
     * @param {fieldId} fieldId - field Path
     * @return {FieldSignature|null}
     */
    getFieldSignatureSafely: function(fieldId) {
        let signature = null;
        let chain;

        if (!fieldId) {
            return signature;
        }

        try {
            chain = this.pathToEntityChain(fieldId);
        } catch (error) {
            this.errorHandler.handle(error);
            return signature;
        }

        const part = _.last(chain);
        if (part && part.field) {
            signature = _.pick(part.field, 'type', 'relationType', 'relatedEntityName');
            if (_.result(part.field.options, 'identifier')) {
                signature.identifier = true;
            }
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
     * @param {fieldId} fieldId
     * @return {propertyPath}
     */
    getRelativePropertyPathByPath: function(fieldId) {
        const fields = [];
        _.each(fieldId.split('+'), function(part, i) {
            let field;
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
     * Converts property path to field path
     *  in case invalid property path, throws EntityError with proper error message
     *
     * @param {propertyPath} propertyPath
     * @return {fieldId}
     * @throws {EntityError} -- in case invalid property path
     */
    getPathByRelativePropertyPath: function(propertyPath) {
        let parts;
        const properties = propertyPath.split('.');
        let entityModel = this.rootEntity;
        try {
            parts = _.map(properties.slice(0, properties.length - 1), function(fieldName) {
                let part = fieldName;
                const fieldData = _.find(entityModel.get('fields'), {name: fieldName});
                if (fieldData.relatedEntityName) {
                    entityModel = this.collection.getEntityModelByClassName(fieldData.relatedEntityName);
                    part += '+' + fieldData.relatedEntityName;
                }
                return part;
            }, this);

            parts.push(properties[properties.length - 1]);
        } catch (e) {
            throw new EntityError('Can not define field path by given property path "' + propertyPath + '"');
        }
        return parts.join('::');
    },

    /**
     * Converts property path to field path
     *  in case invalid property path, handles the error and returns empty field path
     *
     * @param {propertyPath} propertyPath
     * @return {fieldId}
     */
    getPathByRelativePropertyPathSafely: function(propertyPath) {
        let fieldId;

        try {
            fieldId = this.getPathByRelativePropertyPath(propertyPath);
        } catch (error) {
            this.errorHandler.handle(error);
        }

        return fieldId || '';
    },

    /**
     * Creates EntityTree object that allows to navigate over entities and their relations
     *
     * @return {Object}
     * @protected
     */
    _getEntityTree: function() {
        const entityTree = {};
        const properties = {};

        this.collection.each(function(entityModel) {
            const entityName = entityModel.get('alias') || entityModel.get('className');
            properties[entityName] = {
                get: this._getEntityTreeNode.bind(this, entityModel),
                enumerable: true
            };
        }, this);

        Object.defineProperties(entityTree, properties);

        return entityTree;
    },

    /**
     * Creates EntityTreeNode for the EntityModel and its field, if the fieldName is specified
     *
     * @param {EntityModel} entityModel
     * @param {string} [fieldName]
     * @return {EntityTreeNode}
     * @protected
     */
    _getEntityTreeNode: function(entityModel, fieldName) {
        const properties = this._getEntityTreeNodeProperties(entityModel, fieldName);
        return new EntityTreeNode(properties);
    },

    /**
     * Prepares properties definition object for EntityTreeNode by the EntityModel and its field (if specified)
     *
     * @param {EntityModel} entityModel
     * @param {string} [fieldName]
     * @return {Object}
     * @protected
     */
    _getEntityTreeNodeProperties: function(entityModel, fieldName) {
        const properties = {};
        const entityData = this._extractEntityData(entityModel);

        if (fieldName) {
            const fieldData = _.find(entityData.fields, {name: fieldName});
            if (fieldData.relatedEntityName) {
                const relatedEntityModel = this.collection.getEntityModelByClassName(fieldData.relatedEntityName);
                if (relatedEntityModel) {
                    _.extend(properties, this._getEntityTreeNodeProperties(relatedEntityModel));
                }
            }
            _.extend(properties, {
                __field: {
                    get: function() {
                        const entityData = this._extractEntityData(entityModel);
                        return _.find(entityData.fields, {name: fieldName});
                    }.bind(this)
                }
            });
        } else {
            _.each(entityData.fields, function(fieldData) {
                properties[fieldData.name] = {
                    get: this._getEntityTreeNode.bind(this, entityModel, fieldData.name),
                    enumerable: true
                };
            }, this);
            _.extend(properties, {
                __entity: {
                    get: this._extractEntityData.bind(this, entityModel)
                }
            });
        }

        return properties;
    },

    /**
     * Parses string of property path and returns EntityTreeNode for it
     *  examples:
     *   - 'user' node represents user entity
     *   - 'Oro\Bundle\UserBundle\Entity\User' node of same entity
     *   - 'user.owner' node represents user.owner field and businessunit entity (related entity)
     *   - 'user.owner.id' node represents businessunit.id field
     *
     * @param {string} propertyPath
     * @return {Object|undefined}
     */
    getEntityTreeNodeByPropertyPath: function(propertyPath) {
        let fieldName;
        let fieldData;
        let entityModel;

        if (!propertyPath) {
            return;
        }

        const path = propertyPath.split('.');
        const entity = path.shift();
        entityModel = this.collection.getEntityModelByClassName(entity);
        if (!entityModel) {
            entityModel = this.collection.find({alias: entity});
        }

        while (entityModel && path.length) {
            fieldName = path.shift();
            fieldData = _.find(this._extractEntityData(entityModel).fields, {name: fieldName});
            if (!fieldData || path.length && !(fieldData.relatedEntityName && fieldData.relationType)) {
                // field with specified name doesn't exist in model
                // or relation to next level is not available
                return;
            }
            if (path.length) {
                // if it is not last element of path -- it is relation to next entity
                entityModel = this.collection.getEntityModelByClassName(fieldData.relatedEntityName);
            }
        }

        return entityModel ? this._getEntityTreeNode(entityModel, fieldName) : void 0;
    }
}, /** @lends EntityStructureDataProvider */{
    /**
     * @type {Object.<string, FilterConfig>}
     * @protected
     * @static
     */
    _filterPresets: {},

    /**
     * Creates instance of data provider and returns it with the promise object
     *
     * @param {Object=} options
     * @param {string} [options.rootEntity] class name of root entity
     * @param {string} [options.filterPreset] name of filter preset
     * @param {Object.<string, boolean>} [options.optionsFilter] acceptable entity's and fields' options
     *  example:
     *      {auditable: true, configurable: true, unidirectional: false}
     * @param {[Object|string]} [options.exclude]
     *  examples:
     *      ['relationType'] - will exclude all entries that has 'relationType' key (means relational fields)
     *      [{type: 'date'}] - will exclude all entries that has property "type" equals to "date"
     * @param {[Object|string]} [options.include]
     *  examples:
     *      ['relationType'] - will include all entries that has 'relationType' key (means relational fields)
     *      [{type: 'date'}] - will include all entries that has property "type" equals to "date"
     * @param {fieldsFilterer} [options.fieldsFilterer]
     * @param {boolean} [options.isRestrictiveWhitelist] - says if only fields from whitelist
     *  has to be represented in results
     * @param {Object.<string, Object.<string, boolean>>} [options.fieldsFilterWhitelist]
     *  whitelist of fields that has NOT to be filtered out
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {groups: true}} - groups field of User entity
     *          has to be included to results, despite it might not pass the filters
     * @param {Object.<string, Object.<string, boolean>>} [options.fieldsFilterBlacklist]
     *  blacklist of fields that HAS to be filtered out
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {groups: true}} - groups field of User entity
     *          has to be excluded from results, despite it might pass the filters
     * @param {Object.<string, Object.<string, Object>>} [options.fieldsDataUpdate]
     *  DataUpdate that has to be applied to fields of filtered results
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {
     *          groups: {type: 'enum'},  // groups field of User entity will be represented as enum
     *          viewHistory: {type: 'collection', label: 'View history'} // new field will be added
     *      }}
     * @param {RegistryApplicant} applicant
     * @return {Promise.<EntityStructureDataProvider>}
     * @static
     */
    createDataProvider: function(options, applicant) {
        let collection = this.registry.fetch(EntityStructuresCollection.prototype.globalId, applicant);
        if (!collection) {
            collection = new EntityStructuresCollection();
            this.registry.put(collection, applicant);
        }

        const provider = new EntityStructureDataProvider(_.defaults({
            collection: collection
        }, options));
        provider.listenToOnce(applicant, 'dispose', provider.dispose);
        provider.listenToOnce(collection, 'proxy-cache:stale-data-in-use', function() {
            provider.errorHandler.handleOutdatedDataError();
        });

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
     * @param {FilterConfig} filterConfig
     * @static
     */
    defineFilterPreset: function(name, filterConfig) {
        if (name in EntityStructureDataProvider._filterPresets) {
            throw new Error('Filter preset with `' + name + '` name already defined');
        }
        EntityStructureDataProvider._filterPresets[name] = filterConfig;
    },

    /**
     * @type {FilterConfig}
     * @static
     * @protected
     */
    _filterConfigDefaults: {
        optionsFilter: {},
        exclude: [],
        include: [],
        fieldsFilterer: null,
        isRestrictiveWhitelist: false,
        fieldsFilterWhitelist: {},
        fieldsFilterBlacklist: {},
        fieldsDataUpdate: {}
    }
});

EntityStructureDataProvider.registry = registry;

Object.defineProperties(EntityStructureDataProvider.prototype, {
    /**
     * Returns EntityTree object that allows to navigate over entities and their relations.
     * Each node can represent entity or/and field.
     *  - root nodes are only entities
     *  - leaf nodes are only fields
     *  - intermediate nodes are both fields and entities, since they represent relation fields
     * Entity node has magic property __entity, returns information about the entity
     * Field node has magic property __field, returns information about the field
     * Both nodes have magic properties __isField and __isEntity
     *
     * @type {Object}
     * @memberOf {EntityStructureDataProvider.prototype}
     */
    entityTree: {
        get: EntityStructureDataProvider.prototype._getEntityTree,
        enumerable: true
    }
});

/**
 * @export oroentity/js/app/services/entity-structure-data-provider
 */
export default EntityStructureDataProvider;
