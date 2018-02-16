define(function(require) {
    'use strict';

    var exposure = require('requirejs-exposure')
        .disclose('oroentity/js/app/models/entity-collection');
    var RegistryMock = require('../../Fixture/app/services/registry/registry-mock');
    var EntityModel = require('oroentity/js/app/models/entity-model');
    var EntityCollection = require('oroentity/js/app/models/entity-collection');

    describe('oroentity/js/app/models/entity-collection', function() {
        var registryMock;

        beforeEach(function() {
            registryMock = new RegistryMock();
            exposure.substitute('registry').by(registryMock);
        });

        afterEach(function() {
            exposure.recover('registry');
        });

        describe('create collection', function() {
            it('entity type is not specified', function() {
                expect(function() {
                    return new EntityCollection();
                }).toThrowError(TypeError, 'Entity type is required for EntityCollection');

                expect(function() {
                    return new EntityCollection({data: [
                        {type: 'users', id: '1'},
                        {type: 'users', id: '2'}
                    ]}, {});
                }).toThrowError(TypeError, 'Entity type is required for EntityCollection');
            });

            it('get unsynced collection', function() {
                var collection = new EntityCollection([], {type: 'users'});
                expect(collection.isSynced()).toBe(false);
                expect(collection.syncState()).toBe('unsynced');
            });

            it('get synced empty collection', function() {
                var collection = new EntityCollection({data: []}, {type: 'users'});
                expect(collection.isSynced()).toBe(true);
                expect(collection.syncState()).toBe('synced');
            });
        });

        describe('collection manipulation', function() {
            var collection;

            beforeEach(function() {
                spyOn(EntityModel, 'getEntityModel').and.callThrough();
                collection = new EntityCollection({data: [
                    {type: 'users', id: '1', attributes: {name: 'John'}},
                    {type: 'users', id: '2', attributes: {name: 'Jack'}}
                ]}, {type: 'users'});
            });

            it('retrieve models from registry', function() {
                expect(EntityModel.getEntityModel.calls.count()).toBe(2);
                expect(EntityModel.getEntityModel).toHaveBeenCalledWith(
                    jasmine.objectContaining({data: {type: 'users', id: '1', attributes: {name: 'John'}}}),
                    collection
                );
                expect(EntityModel.getEntityModel).toHaveBeenCalledWith(
                    jasmine.objectContaining({data: {type: 'users', id: '2', attributes: {name: 'Jack'}}}),
                    collection
                );
            });

            it('get model id', function() {
                expect(collection.modelId({data: {type: 'users', id: '2'}})).toBe('2');
                expect(collection.modelId({type: 'users', id: '1'})).toBe('1');
            });

            it('remove model', function() {
                var model1 = collection.get('1');
                collection.remove(model1);
                expect(registryMock.relieve).toHaveBeenCalledWith(model1, collection);
            });

            it('relieve models on reset collections', function() {
                var model2 = collection.get('2');
                collection.reset({data: [
                    {type: 'users', id: '1'},
                    {type: 'users', id: '3'}
                ]}, {parse: true});
                expect(registryMock.relieve).toHaveBeenCalledWith(model2, collection);
            });

            it('convert collection to JSON', function() {
                expect(collection.toJSON()).toEqual({
                    data: [
                        {type: 'users', id: '1', attributes: {name: 'John'}},
                        {type: 'users', id: '2', attributes: {name: 'Jack'}}
                    ]
                });
            });

            it('serialize collection', function() {
                var data = collection.serialize();
                expect(data).toEqual([{}, {}]);
                expect(Object.getPrototypeOf(data[0])).toEqual({
                    type: 'users', id: '1', name: 'John', toString: jasmine.any(Function)
                });
                expect(Object.getPrototypeOf(data[1])).toEqual({
                    type: 'users', id: '2', name: 'Jack', toString: jasmine.any(Function)
                });
            });
        });
    });
});
