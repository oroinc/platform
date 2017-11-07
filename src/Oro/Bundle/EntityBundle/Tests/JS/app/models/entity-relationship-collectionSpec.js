define(function(require) {
    'use strict';

    var Backbone = require('backbone');
    var exposure = require('requirejs-exposure')
        .disclose('oroentity/js/app/models/entity-collection');
    var RegistryMock = require('../../Fixture/app/services/registry/registry-mock');
    var EntityRelationshipCollection = require('oroentity/js/app/models/entity-relationship-collection');

    describe('oroentity/js/app/models/entity-relationship-collection', function() {
        var applicant1;
        var applicant2;
        var registryMock;

        beforeEach(function() {
            applicant1 = Object.create(Backbone.Events);
            applicant2 = Object.create(Backbone.Events);
            registryMock = new RegistryMock();
            registryMock.getEntityRelationshipCollection = function(params, applicant) {
                return EntityRelationshipCollection
                    .getEntityRelationshipCollection(registryMock, params, applicant);
            };
            spyOn(registryMock, 'getEntityRelationshipCollection').and.callThrough();
            exposure.substitute('registry').by(registryMock);
        });

        afterEach(function() {
            exposure.recover('registry');
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
            it('catch error on invalid identifier', function() {
                expect(function() {
                    EntityRelationshipCollection.getEntityRelationshipCollection(
                        registryMock, {type: 'test', id: '13', association: null}, applicant1);
                }).toThrow();
            });

            it('get synced collection with models', function() {
                var collection = EntityRelationshipCollection.getEntityRelationshipCollection(registryMock, {
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

                expect(registryMock.getEntity.calls.count()).toBe(2);
                expect(registryMock.getEntity).toHaveBeenCalledWith(
                    jasmine.objectContaining({data: {type: 'users', id: '1'}}), collection);
                expect(registryMock.getEntity).toHaveBeenCalledWith(
                    jasmine.objectContaining({data: {type: 'users', id: '2'}}), collection);
            });

            it('create new relationship collection', function() {
                var collection = EntityRelationshipCollection.getEntityRelationshipCollection(
                    registryMock, {type: 'test', id: '13', association: 'users'}, applicant1);

                expect(registryMock.getEntry).toHaveBeenCalledWith('test::13::users', applicant1);
                expect(registryMock.registerInstance)
                    .toHaveBeenCalledWith(jasmine.any(EntityRelationshipCollection), applicant1);
                expect(collection).toEqual(jasmine.any(EntityRelationshipCollection));
            });

            it('retrieve existing relationship collection', function() {
                var collection1 = new EntityRelationshipCollection(
                    null, {type: 'test', id: '13', association: 'users'});
                registryMock._entries[collection1.globalId] = {instance: collection1};

                var collection2 = EntityRelationshipCollection.getEntityRelationshipCollection(
                    registryMock, {type: 'test', id: '13', association: 'users'}, applicant1);

                expect(registryMock.getEntry).toHaveBeenCalledWith('test::13::users', applicant1);
                expect(registryMock.registerInstance).not.toHaveBeenCalled();
                expect(collection2).toBe(collection1);
            });

            it('retrieve existing relationship collection with update', function() {
                var collection1 = EntityRelationshipCollection.getEntityRelationshipCollection(
                    registryMock, {type: 'test', id: '13', association: 'users'}, applicant1);
                var collection2 = EntityRelationshipCollection.getEntityRelationshipCollection(registryMock, {
                    type: 'test',
                    id: '13',
                    association: 'users',
                    data: [{type: 'users', id: '1'}, {type: 'users', id: '2'}]
                }, applicant2);

                expect(collection1).toBe(collection2);
                expect(collection1.size()).toBe(2);
            });

            it('retrieve existing relationship collection without update', function() {
                var collection1 = EntityRelationshipCollection.getEntityRelationshipCollection(registryMock, {
                    type: 'test',
                    id: '13',
                    association: 'users',
                    data: [{type: 'users', id: '1'}, {type: 'users', id: '2'}]
                }, applicant2);
                var collection2 = EntityRelationshipCollection.getEntityRelationshipCollection(
                    registryMock, {type: 'test', id: '13', association: 'users'}, applicant1);

                expect(collection1).toBe(collection2);
                expect(collection2.size()).toBe(2);
            });
        });

        describe('collection manipulation', function() {
            var collection;

            beforeEach(function() {
                collection = new EntityRelationshipCollection({data: [
                    {type: 'users', id: '1'},
                    {type: 'users', id: '2'}
                ]}, {type: 'test', id: '13', association: 'users'});
            });

            it('collection globalId', function() {
                expect(collection.globalId).toBe('test::13::users');
            });

            it('collection identifier', function() {
                expect(collection.identifier).toEqual({type: 'test', id: '13', association: 'users'});
            });
        });
    });
});
