define(function(require) {
    'use strict';

    const Backbone = require('backbone');
    const entityRelationshipCollectionModuleInjector = require('inject-loader!oroentity/js/app/models/entity-relationship-collection');

    const EntityModel = require('oroentity/js/app/models/entity-model');
    const RegistryMock = require('../../Fixture/app/services/registry/registry-mock');

    describe('oroentity/js/app/models/entity-relationship-collection', function() {
        let applicant1;
        let applicant2;
        let registryMock;
        let EntityRelationshipCollection;

        beforeEach(function() {
            applicant1 = Object.create(Backbone.Events);
            applicant2 = Object.create(Backbone.Events);
            registryMock = new RegistryMock();
            EntityRelationshipCollection = entityRelationshipCollectionModuleInjector({
                'oroui/js/app/services/registry': registryMock
            });
        });

        it('static method EntityRelationshipCollection.globalId', function() {
            expect(EntityRelationshipCollection.globalId({
                type: 'test',
                id: '13',
                association: 'users'
            })).toBe('test::13::users');
        });

        it('static method EntityRelationshipCollection.isValidIdentifier', function() {
            expect(EntityRelationshipCollection.isValidIdentifier({
                type: 'test',
                id: '13',
                association: 'users'
            })).toBe(true);

            expect(EntityRelationshipCollection
                .isValidIdentifier({type: 'test', id: '13'})).toBe(false);
            expect(EntityRelationshipCollection
                .isValidIdentifier({type: 'test', id: '13', association: ''})).toBe(false);
            expect(EntityRelationshipCollection
                .isValidIdentifier({type: 'test', id: '13', association: 7})).toBe(false);
        });

        describe('static method EntityRelationshipCollection.getEntityRelationshipCollection', function() {
            beforeEach(function() {
                spyOn(EntityModel, 'getEntityModel').and.callThrough();
            });

            it('catch error on invalid identifier', function() {
                expect(function() {
                    EntityRelationshipCollection
                        .getEntityRelationshipCollection({type: 'test', id: '13', association: null}, applicant1);
                }).toThrow();
            });

            it('get synced collection with models', function() {
                const collection = EntityRelationshipCollection.getEntityRelationshipCollection({
                    type: 'test',
                    id: '13',
                    association: 'users',
                    data: [
                        {type: 'users', id: '1'},
                        {type: 'users', id: '2'}
                    ]
                }, applicant1);

                expect(collection.isSynced()).toBe(true);
                expect(collection.syncState()).toBe('synced');

                expect(EntityModel.getEntityModel.calls.count()).toBe(2);
                expect(EntityModel.getEntityModel).toHaveBeenCalledWith(
                    jasmine.objectContaining({data: {type: 'users', id: '1'}}), collection);
                expect(EntityModel.getEntityModel).toHaveBeenCalledWith(
                    jasmine.objectContaining({data: {type: 'users', id: '2'}}), collection);
            });

            it('create new relationship collection', function() {
                const collection = EntityRelationshipCollection
                    .getEntityRelationshipCollection({type: 'test', id: '13', association: 'users'}, applicant1);

                expect(registryMock.fetch).toHaveBeenCalledWith('test::13::users', applicant1);
                expect(registryMock.put).toHaveBeenCalledWith(jasmine.any(EntityRelationshipCollection), applicant1);
                expect(collection).toEqual(jasmine.any(EntityRelationshipCollection));
            });

            it('retrieve existing relationship collection', function() {
                const collection1 = new EntityRelationshipCollection(
                    null, {type: 'test', id: '13', association: 'users'});
                registryMock._entries[collection1.globalId] = {instance: collection1};

                const collection2 = EntityRelationshipCollection
                    .getEntityRelationshipCollection({type: 'test', id: '13', association: 'users'}, applicant1);

                expect(registryMock.fetch).toHaveBeenCalledWith('test::13::users', applicant1);
                expect(registryMock.retain).not.toHaveBeenCalled();
                expect(collection2).toBe(collection1);
            });

            it('retrieve existing relationship collection with update', function() {
                const collection1 = EntityRelationshipCollection
                    .getEntityRelationshipCollection({type: 'test', id: '13', association: 'users'}, applicant1);
                const collection2 = EntityRelationshipCollection
                    .getEntityRelationshipCollection({
                        type: 'test',
                        id: '13',
                        association: 'users',
                        data: [{type: 'users', id: '1'}, {type: 'users', id: '2'}]
                    }, applicant2);

                expect(collection1).toBe(collection2);
                expect(collection1.size()).toBe(2);
            });

            it('retrieve existing relationship collection without update', function() {
                const collection1 = EntityRelationshipCollection.getEntityRelationshipCollection({
                    type: 'test',
                    id: '13',
                    association: 'users',
                    data: [{type: 'users', id: '1'}, {type: 'users', id: '2'}]
                }, applicant2);
                const collection2 = EntityRelationshipCollection
                    .getEntityRelationshipCollection({type: 'test', id: '13', association: 'users'}, applicant1);

                expect(collection1).toBe(collection2);
                expect(collection2.size()).toBe(2);
            });
        });

        describe('collection manipulation', function() {
            let collection;

            beforeEach(function() {
                collection = new EntityRelationshipCollection({data: [
                    {type: 'users', id: '1', attributes: {name: 'John'}},
                    {type: 'users', id: '2', attributes: {name: 'Jack'}}
                ]}, {type: 'test', id: '13', association: 'users'});
            });

            it('collection globalId', function() {
                expect(collection.globalId).toBe('test::13::users');
            });

            it('collection identifier', function() {
                expect(collection.identifier).toEqual({type: 'test', id: '13', association: 'users'});
            });

            it('convert collection to JSON', function() {
                expect(collection.toJSON()).toEqual({
                    data: [
                        {type: 'users', id: '1'},
                        {type: 'users', id: '2'}
                    ]
                });
            });
        });
    });
});
