define(function(require) {
    'use strict';

    const Backbone = require('backbone');
    const $ = require('jquery');
    const _ = require('underscore');
    const data = require('../../Fixture/app/services/entitystructure-data.json');
    const RegistryMock = require('../../Fixture/app/services/registry/registry-mock');
    const providerModuleInjector = require('inject-loader!oroentity/js/app/services/entity-structure-data-provider');
    const entityModelModuleInjector = require('inject-loader!oroentity/js/app/models/entity-model');
    const entityCollectionModuleInjector = require('inject-loader!oroentity/js/app/models/entity-collection');
    const entityStructuresCollectionModuleInjector = require('inject-loader!oroentity/js/app/models/entitystructures-collection');

    const routing = {
        generate: jasmine.createSpy('entitySync').and.returnValue('test/url')
    };

    const EntityModel = entityModelModuleInjector({
        routing: routing
    });

    describe('oroentity/js/app/services/entity-structure-data-provider', function() {
        let applicant1;
        let applicant2;
        let registryMock;
        let entitySyncMock;
        let EntityStructuresCollection;
        let EntityStructureDataProvider;

        beforeEach(function() {
            applicant1 = Object.create(Backbone.Events);
            applicant2 = Object.create(Backbone.Events);

            registryMock = new RegistryMock();

            entitySyncMock = jasmine.createSpy('entitySync').and.callFake(function(method, model, options) {
                // mocks fetch collection action
                const deferred = $.Deferred();
                const xhrMock = deferred.promise();
                model.trigger('request', model, xhrMock, options);
                deferred.done(options.success).resolve(data);
                return xhrMock;
            });

            const EntityCollection = entityCollectionModuleInjector({
                'oroui/js/app/services/registry': registryMock,
                'oroentity/js/app/models/entity-sync': entitySyncMock,
                'oroentity/js/app/models/entity-model': EntityModel,
                'routing': routing
            });

            EntityStructuresCollection = entityStructuresCollectionModuleInjector({
                'oroentity/js/app/models/entity-collection': EntityCollection
            });

            EntityStructureDataProvider = providerModuleInjector({
                'oroui/js/app/services/registry': registryMock,
                'oroentity/js/app/models/entitystructures-collection': EntityStructuresCollection
            });
        });

        describe('entity structures data provider', function() {
            let dataProvider;
            const initialRootEntityClassName = 'Oro\\Bundle\\UserBundle\\Entity\\User';
            const fieldIdParts = [
                'roles',
                'Oro\\Bundle\\UserBundle\\Entity\\Role::Oro\\Bundle\\UserBundle\\Entity\\Group::roles',
                'Oro\\Bundle\\UserBundle\\Entity\\Group::name'
            ];
            const fieldId = fieldIdParts.join('+');
            const chain = [
                {
                    path: '',
                    basePath: '',
                    entity: {
                        className: 'Oro\\Bundle\\UserBundle\\Entity\\User'
                    }
                },
                {
                    path: 'roles',
                    basePath: ['roles', 'Oro\\Bundle\\UserBundle\\Entity\\Role'].join('+'),
                    field: {
                        name: 'roles',
                        relatedEntityName: 'Oro\\Bundle\\UserBundle\\Entity\\Role'
                    },
                    entity: {
                        className: 'Oro\\Bundle\\UserBundle\\Entity\\Role'
                    }
                },
                {
                    path: [
                        'roles',
                        'Oro\\Bundle\\UserBundle\\Entity\\Role::Oro\\Bundle\\UserBundle\\Entity\\Group::roles'
                    ].join('+'),
                    basePath: [
                        'roles',
                        'Oro\\Bundle\\UserBundle\\Entity\\Role::Oro\\Bundle\\UserBundle\\Entity\\Group::roles',
                        'Oro\\Bundle\\UserBundle\\Entity\\Group'
                    ].join('+'),
                    field: {
                        name: 'Oro\\Bundle\\UserBundle\\Entity\\Group::roles',
                        relatedEntityName: 'Oro\\Bundle\\UserBundle\\Entity\\Group'
                    },
                    entity: {
                        className: 'Oro\\Bundle\\UserBundle\\Entity\\Group'
                    }
                },
                {
                    path: [
                        'roles',
                        'Oro\\Bundle\\UserBundle\\Entity\\Role::Oro\\Bundle\\UserBundle\\Entity\\Group::roles',
                        'Oro\\Bundle\\UserBundle\\Entity\\Group::name'
                    ].join('+'),
                    field: {
                        name: 'name'
                    }
                }
            ];
            const chainMock = chain.map(function(part) {
                const partMock = _.extend({}, part);
                if (partMock.entity) {
                    partMock.entity = jasmine.objectContaining(_.extend({}, part.entity, {
                        fields: jasmine.any(Array)
                    }));
                }
                if (partMock.field) {
                    partMock.field = jasmine.objectContaining(_.extend({}, part.field));
                }
                return partMock;
            });

            beforeEach(async function() {
                return EntityStructureDataProvider
                    .createDataProvider({rootEntity: initialRootEntityClassName}, applicant1)
                    .then(provider => dataProvider = provider);
            });

            it('data provider is instance of `EntityStructureDataProvider`', function() {
                expect(dataProvider).toEqual(jasmine.any(EntityStructureDataProvider));
                expect(dataProvider.collection).toEqual(jasmine.any(EntityStructuresCollection));
                expect(registryMock.put).toHaveBeenCalledWith(dataProvider.collection, applicant1);
            });

            it('data provider\'s destructor does not dispose collection', function() {
                const collection = dataProvider.collection;
                dataProvider.dispose();
                expect(collection.disposed).not.toBe(true);
                expect(Object.isFrozen(collection)).not.toBe(true);
            });

            it('get routes of root entity', function() {
                expect(dataProvider.getEntityRoutes()).toEqual({
                    name: 'oro_user_index',
                    view: 'oro_user_view'
                });
            });

            it('defines entity chain for empty path', function() {
                expect(dataProvider.pathToEntityChain()).toEqual(chainMock.slice(0, 1));
            });

            it('defines entity chain from fieldId path', function() {
                expect(dataProvider.pathToEntityChain(fieldId)).toEqual(chainMock);
            });

            it('defines entity chain from fieldId path excluding trailing field', function() {
                expect(dataProvider.pathToEntityChainExcludeTrailingField(fieldId))
                    .toEqual(chainMock.slice(0, chainMock.length - 1));
            });

            it('defines fieldId path from entity chain', function() {
                expect(dataProvider.entityChainToPath(chain)).toEqual(fieldId);
            });

            it('defines field signature from entity fieldId path', function() {
                expect(dataProvider.getFieldSignatureSafely(fieldId)).toEqual({
                    type: 'string',
                    field: 'name',
                    entity: 'Oro\\Bundle\\UserBundle\\Entity\\Group',
                    parent_entity: 'Oro\\Bundle\\UserBundle\\Entity\\Role'
                });

                expect(dataProvider.getFieldSignatureSafely(fieldIdParts.slice(0, 2).join('+'))).toEqual({
                    relationType: 'manyToMany',
                    field: 'Oro\\Bundle\\UserBundle\\Entity\\Group::roles',
                    entity: 'Oro\\Bundle\\UserBundle\\Entity\\Role',
                    parent_entity: 'Oro\\Bundle\\UserBundle\\Entity\\User'
                });

                expect(dataProvider.getFieldSignatureSafely(fieldIdParts[0])).toEqual({
                    relationType: 'manyToMany',
                    field: 'roles',
                    entity: 'Oro\\Bundle\\UserBundle\\Entity\\User'
                });
            });

            it('converts fieldId path to property path', function() {
                expect(dataProvider.getPropertyPathByPath(fieldId))
                    .toEqual('roles.Oro\\Bundle\\UserBundle\\Entity\\Group::roles.name');
            });

            it('converts property path to fieldId path', function() {
                expect(dataProvider.getPathByPropertyPath('roles.Oro\\Bundle\\UserBundle\\Entity\\Group::roles.name'))
                    .toEqual(fieldId);
            });

            describe('compare two data providers', function() {
                let dataProvider2;

                beforeEach(async function() {
                    return EntityStructureDataProvider
                        .createDataProvider({}, applicant2)
                        .then(provider => dataProvider2 = provider);
                });

                it('create each time a new data provider', function() {
                    expect(dataProvider2).toEqual(jasmine.any(EntityStructureDataProvider));
                    expect(dataProvider2).not.toBe(dataProvider);
                });

                it('each data provider shares same instance entity structures collection', function() {
                    expect(dataProvider2.collection).toBe(dataProvider.collection);
                });

                it('lifecycle of data provider is bound to applicant', function() {
                    applicant2.trigger('dispose', applicant2);
                    expect(dataProvider2.disposed).toBe(true);
                    expect(Object.isFrozen(dataProvider2)).toBe(true);
                });
            });
        });

        describe('entity structures data provider with predefined options', function() {
            let dataProvider;
            const initialRootEntityClassName = 'Oro\\Bundle\\UserBundle\\Entity\\User';

            beforeEach(async function() {
                return EntityStructureDataProvider.createDataProvider({
                    rootEntity: initialRootEntityClassName,
                    optionsFilter: {configurable: true},
                    exclude: [{name: 'createdAt'}],
                    include: ['type']
                }, applicant1).then(provider => dataProvider = provider);
            });

            it('root entity is defined over initial options', function() {
                expect(dataProvider.rootEntity).toEqual(jasmine.any(EntityModel));
                expect(dataProvider.rootEntity.get('className')).toBe(initialRootEntityClassName);
            });

            it('change root entity in runtime', function() {
                const newRootEntityClassName = 'Oro\\Bundle\\UserBundle\\Entity\\Role';
                const initialRootEntity = dataProvider.rootEntity;
                dataProvider.setRootEntityClassName(newRootEntityClassName);
                expect(dataProvider.rootEntity).not.toBe(initialRootEntity);
                expect(dataProvider.rootEntity.get('className')).not.toBe(initialRootEntityClassName);
                expect(dataProvider.rootEntity.get('className')).toBe(newRootEntityClassName);
            });

            it('capability options are defined over initial options', function() {
                expect(dataProvider.optionsFilter).toEqual({configurable: true});
            });

            it('changes capability options in runtime', function() {
                dataProvider.setOptionsFilter({configurable: true, auditable: true});
                expect(dataProvider.optionsFilter).toEqual({configurable: true, auditable: true});
            });

            it('filter entity fields using include and exclude options', function() {
                expect(dataProvider.pathToEntityChain()).toEqual([jasmine.objectContaining({
                    entity: jasmine.objectContaining({
                        className: 'Oro\\Bundle\\UserBundle\\Entity\\User',
                        fields: [
                            jasmine.objectContaining({name: 'id', type: 'integer'}),
                            jasmine.objectContaining({name: 'firstName', type: 'string'})
                        ]
                    })
                })]);
            });

            it('filter entity fields using filterer callback function', function() {
                dataProvider.fieldsFilterer = jasmine.createSpy('entitySync')
                    .and.callFake(function(entityName, entityFields) {
                        return entityFields.filter(function(field) {
                            return field.name === 'id';
                        });
                    });
                const chain = dataProvider.pathToEntityChain();
                expect(dataProvider.fieldsFilterer).toHaveBeenCalled();
                expect(chain[0].entity).toEqual(jasmine.objectContaining({
                    className: 'Oro\\Bundle\\UserBundle\\Entity\\User',
                    fields: [
                        jasmine.objectContaining({name: 'id', type: 'integer'})
                    ]
                }));
            });

            it('reset filters configuration', function() {
                dataProvider.setOptionsFilter(null);
                dataProvider.setExcludeRules(null);
                dataProvider.setIncludeRules(null);
                const chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields.length).toBe(6);
            });
        });

        describe('filter fields in data provider with advanced options', function() {
            let dataProvider;

            beforeEach(async function() {
                return EntityStructureDataProvider.createDataProvider({
                    rootEntity: 'Oro\\Bundle\\UserBundle\\Entity\\Group'
                }, applicant1).then(provider => dataProvider = provider);
            });

            it('filter by unidirectional option', function() {
                dataProvider.setOptionsFilter({unidirectional: true});
                let chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity).toEqual(jasmine.objectContaining({
                    fields: [
                        jasmine.objectContaining({
                            label: 'Groups (Users)',
                            name: 'Oro\\Bundle\\UserBundle\\Entity\\User::groups'
                        })
                    ]
                }));

                dataProvider.setOptionsFilter({unidirectional: false});
                chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields.length).toBe(3);
                expect(chain[0].entity.fields)
                    .not.toContain(jasmine.objectContaining({
                        label: 'Groups (Users)',
                        name: 'Oro\\Bundle\\UserBundle\\Entity\\User::groups'
                    }));
            });

            it('filter by auditable option', function() {
                dataProvider.setOptionsFilter({auditable: true});
                let chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields).toEqual([
                    jasmine.objectContaining({label: 'Name', name: 'name'}),
                    jasmine.objectContaining({
                        label: 'Groups (Users)',
                        name: 'Oro\\Bundle\\UserBundle\\Entity\\User::groups'
                    })
                ]);

                dataProvider.setOptionsFilter({auditable: false});
                chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields).toEqual([
                    jasmine.objectContaining({label: 'Id', name: 'id'}),
                    jasmine.objectContaining({label: 'Roles', name: 'roles'})
                ]);
            });

            it('filter by relation option', function() {
                dataProvider.setOptionsFilter({relation: true});
                let chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields).toEqual([
                    jasmine.objectContaining({
                        label: 'Groups (Users)',
                        name: 'Oro\\Bundle\\UserBundle\\Entity\\User::groups'
                    }),
                    jasmine.objectContaining({label: 'Roles', name: 'roles'})
                ]);

                dataProvider.setOptionsFilter({relation: false});
                chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields).toEqual([
                    jasmine.objectContaining({label: 'Id', name: 'id'}),
                    jasmine.objectContaining({label: 'Name', name: 'name'})
                ]);
            });
        });

        describe('filter configuration preset is used in provider', function() {
            let dataProvider;

            beforeEach(async function() {
                EntityStructureDataProvider.defineFilterPreset('first-custom-fields-set', {
                    optionsFilter: {configurable: true},
                    include: [{relationType: 'manyToMany'}],
                    exclude: []
                });
                EntityStructureDataProvider.defineFilterPreset('second-custom-fields-set', {
                    optionsFilter: {virtual: true},
                    include: [],
                    exclude: [{relationType: 'manyToMany'}]
                });
                return EntityStructureDataProvider.createDataProvider({
                    rootEntity: 'Oro\\Bundle\\UserBundle\\Entity\\Group',
                    filterPreset: 'first-custom-fields-set'
                }, applicant1).then(provider => dataProvider = provider);
            });

            afterEach(function() {
                delete EntityStructureDataProvider.filterPresets['first-custom-fields-set'];
                delete EntityStructureDataProvider.filterPresets['second-custom-fields-set'];
            });

            it('uses initial filter configuration preset', function() {
                const chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields).toEqual([
                    jasmine.objectContaining({name: 'roles', label: 'Roles'})
                ]);
            });

            it('change filter configuration preset in runtime', function() {
                dataProvider.setFilterPreset('second-custom-fields-set');
                const chain = dataProvider.pathToEntityChain();
                expect(chain[0].entity.fields).toEqual([
                    jasmine.objectContaining({name: 'name', label: 'Name'})
                ]);
            });
        });

        describe('handle errors of invalid input data for entity structures data provider', function() {
            let errorHandler;
            let dataProvider;
            const initialRootEntityClassName = 'Oro\\Bundle\\UserBundle\\Entity\\User';

            beforeEach(async function() {
                errorHandler = jasmine.createSpyObj('errorHandler', ['handle']);
                return EntityStructureDataProvider.createDataProvider({
                    rootEntity: initialRootEntityClassName,
                    errorHandler: errorHandler
                }, applicant1).then(provider => dataProvider = provider);
            });

            it('error on invalid path to entity chain', function() {
                const fieldId = 'roles+Some\\Bundle\\Role::roles';
                expect(dataProvider.pathToEntityChainSafely(fieldId)).toEqual([]);
                expect(errorHandler.handle).toHaveBeenCalledWith(jasmine.any(Error));
                expect(function() {
                    dataProvider.pathToEntityChain(fieldId);
                }).toThrow(jasmine.any(Error));
            });

            it('error on invalid entity chain to path', function() {
                const chain = [{foo: 'test'}, {bar: 'baz'}];
                expect(dataProvider.entityChainToPathSafely(chain)).toEqual('');
                expect(errorHandler.handle).toHaveBeenCalledWith(jasmine.any(Error));
                expect(function() {
                    dataProvider.entityChainToPath(chain);
                }).toThrow(jasmine.any(Error));
            });

            it('error on invalid property path to path', function() {
                const propertyPath = 'foo.bar';
                expect(dataProvider.getPathByPropertyPathSafely(propertyPath)).toEqual('');
                expect(errorHandler.handle).toHaveBeenCalledWith(jasmine.any(Error));
                expect(function() {
                    dataProvider.getPathByPropertyPath(propertyPath);
                }).toThrow(jasmine.any(Error));
            });
        });
    });
});
