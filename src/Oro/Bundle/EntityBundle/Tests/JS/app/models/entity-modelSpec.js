define(function(require) {
    'use strict';

    var Backbone = require('backbone');
    var exposure = require('requirejs-exposure')
        .disclose('oroentity/js/app/models/entity-model');
    var RegistryMock = require('../../Fixture/app/services/registry/registry-mock');
    var EntityModel = require('oroentity/js/app/models/entity-model');

    describe('oroentity/js/app/models/entity-model', function() {
        var applicant1;
        var applicant2;
        var registryMock;

        beforeEach(function() {
            applicant1 = Object.create(Backbone.Events);
            applicant2 = Object.create(Backbone.Events);
            registryMock = new RegistryMock();
            exposure.substitute('registry').by(registryMock);
        });

        afterEach(function() {
            exposure.recover('registry');
        });

        it('static method EntityModel.globalId', function() {
            expect(EntityModel.globalId({type: 'test', id: '13'})).toBe('test::13');
            expect(EntityModel.globalId({type: 'priority', id: 'normal'})).toBe('priority::normal');
        });

        it('static method EntityModel.isValidIdentifier', function() {
            expect(EntityModel.isValidIdentifier({type: 'test', id: '13'})).toBe(true);
            expect(EntityModel.isValidIdentifier({type: 'priority', id: 'normal'})).toBe(true);

            expect(EntityModel.isValidIdentifier({})).toBe(false);
            expect(EntityModel.isValidIdentifier({type: null, id: '13'})).toBe(false);
            expect(EntityModel.isValidIdentifier({type: 'test', id: 13})).toBe(false);
            expect(EntityModel.isValidIdentifier({type: 'priority', id: null})).toBe(false);
        });

        describe('static method EntityModel.getEntityModel', function() {
            it('catch error on invalid identifier', function() {
                expect(function() {
                    EntityModel.getEntityModel({type: 'test', id: null}, applicant1);
                }).toThrow();
            });

            it('get unsynced model', function() {
                var entity = EntityModel.getEntityModel({
                    data: {
                        type: 'test',
                        id: '13'
                    }
                }, applicant1);
                expect(entity.isSynced()).toBe(false);
                expect(entity.syncState()).toBe('unsynced');
            });

            it('get synced model', function() {
                var entity = EntityModel.getEntityModel({
                    data: {
                        type: 'test',
                        id: '13',
                        attributes: {
                            subject: 'it is test'
                        }
                    }
                }, applicant1);
                expect(entity.isSynced()).toBe(true);
                expect(entity.syncState()).toBe('synced');
            });

            it('create new entity model', function() {
                var entity = EntityModel.getEntityModel({type: 'test', id: '13'}, applicant1);
                expect(registryMock.fetch).toHaveBeenCalledWith('test::13', applicant1);
                expect(registryMock.put).toHaveBeenCalledWith(jasmine.any(EntityModel), applicant1);
                expect(entity).toEqual(jasmine.any(EntityModel));
            });

            it('retrieve existing entity model', function() {
                var model = new EntityModel(null, {type: 'test', id: '13'});
                registryMock._entries[model.globalId] = {instance: model};

                var entity = EntityModel.getEntityModel({type: 'test', id: '13'}, applicant1);
                expect(registryMock.fetch).toHaveBeenCalledWith('test::13', applicant1);
                expect(registryMock.put).not.toHaveBeenCalled();
                expect(entity).toBe(model);
            });

            it('retrieve entity model with self reference relation', function() {
                var entity = EntityModel.getEntityModel({
                    data: {
                        type: 'test',
                        id: '13',
                        attributes: {
                            title: 'Entity with self reference relation'
                        },
                        relationships: {
                            ref: {
                                data: {
                                    type: 'test',
                                    id: '13'
                                }
                            }
                        }
                    }
                }, applicant1);

                expect(entity).toEqual(jasmine.any(EntityModel));
                expect(entity.getRelationship('ref', applicant1)).toBe(entity);

                expect(registryMock.fetch.calls.count()).toBe(4);
                expect(registryMock.fetch.calls.argsFor(0)).toEqual(['test::13', applicant1]);
                expect(registryMock.fetch.calls.all()[0].returnValue).toBe(null);
                expect(registryMock.fetch.calls.argsFor(1)).toEqual(['test::13', jasmine.any(EntityModel)]);
                expect(registryMock.fetch.calls.all()[1].returnValue).toBe(null);
                expect(registryMock.fetch.calls.argsFor(2)).toEqual(['test::13', applicant1]);
                expect(registryMock.fetch.calls.all()[2].returnValue).toEqual(entity);
                expect(registryMock.fetch.calls.argsFor(3)).toEqual(['test::13', entity]);
                expect(registryMock.fetch.calls.all()[3].returnValue).toEqual(entity);

                expect(registryMock.put.calls.count()).toBe(2);
                expect(registryMock.put.calls.first().args).toEqual([entity, jasmine.any(EntityModel)]);
                expect(registryMock.put.calls.first().returnValue).toEqual({instance: entity});
                expect(registryMock.put.calls.mostRecent().args).toEqual([jasmine.any(EntityModel), applicant1]);
                expect(registryMock.put.calls.mostRecent().returnValue).toBe(undefined);
            });

            it('retrieve existing entity model with attributes update', function() {
                var entity1 = EntityModel.getEntityModel({type: 'test', id: '13'}, applicant1);
                var entity2 = EntityModel.getEntityModel({
                    data: {
                        type: 'test',
                        id: '13',
                        attributes: {
                            title: 'Synced entity'
                        }
                    }
                }, applicant2);

                expect(entity1).toBe(entity2);
                expect(entity1.get('title')).toBe('Synced entity');
            });

            it('retrieve existing entity model without attributes update', function() {
                var entity1 = EntityModel.getEntityModel({
                    data: {
                        type: 'test',
                        id: '13',
                        attributes: {
                            title: 'Synced entity'
                        }
                    }
                }, applicant2);
                var entity2 = EntityModel.getEntityModel({type: 'test', id: '13'}, applicant1);

                expect(entity1).toBe(entity2);
                expect(entity1.get('title')).toBe('Synced entity');
            });
        });

        describe('model manipulation', function() {
            var model;

            beforeEach(function() {
                model = new EntityModel({data: {type: 'task', id: '12'}});
            });

            it('collection identifier', function() {
                expect(model.identifier).toEqual({type: 'task', id: '12'});
            });

            it('collection globalId', function() {
                expect(model.globalId).toBe('task::12');
            });

            it('get model id', function() {
                expect(model.get('id')).toBe('12');
            });
        });

        describe('init model with raw data without includes', function() {
            var entityModel;
            var rawData = {
                data: {
                    type: 'tasks',
                    id: '12',
                    attributes: {
                        subject: 'Wake up'
                    },
                    relationships: {
                        owner: {
                            data: {
                                type: 'users',
                                id: '1'
                            }
                        },
                        organization: {
                            data: {
                                type: 'organizations',
                                id: '1'
                            }
                        }
                    }
                }
            };

            beforeEach(function() {
                spyOn(EntityModel, 'getEntityModel').and.callThrough();
                entityModel = new EntityModel(rawData);
            });

            it('init relationships', function() {
                expect(EntityModel.getEntityModel.calls.count()).toBe(2);
                expect(EntityModel.getEntityModel).toHaveBeenCalledWith({data: {type: 'users', id: '1'}}, entityModel);
                expect(EntityModel.getEntityModel.calls.all()[0].returnValue).toEqual(jasmine.any(EntityModel));
                expect(EntityModel.getEntityModel).toHaveBeenCalledWith(
                    {data: {type: 'organizations', id: '1'}}, entityModel);
                expect(EntityModel.getEntityModel.calls.all()[1].returnValue).toEqual(jasmine.any(EntityModel));
            });

            it('get relationships', function() {
                var relatedEntityModel = entityModel.getRelationship('owner', applicant1);
                expect(relatedEntityModel).toEqual(jasmine.any(EntityModel));
                expect(registryMock.retain.calls.mostRecent().args)
                    .toEqual([relatedEntityModel, applicant1]);
            });

            it('change relationships', function() {
                var oldOwnerModel = entityModel.getRelationship('owner', applicant1);
                entityModel.set('owner', {data: {type: 'users', id: '7'}});

                expect(registryMock.relieve.calls.mostRecent().args)
                    .toEqual([oldOwnerModel, entityModel]);

                var newOwnerModel = entityModel.getRelationship('owner', applicant1);
                expect(registryMock.retain.calls.mostRecent().args)
                    .toEqual([newOwnerModel, applicant1]);

                expect(newOwnerModel).not.toBe(oldOwnerModel);
            });

            it('unset relationships', function() {
                entityModel.set('owner', {data: null});
                var relatedEntityModel = entityModel.getRelationship('owner', applicant1);

                expect(relatedEntityModel).toBe(null);
                expect(registryMock.relieve.calls.mostRecent().args)
                    .toEqual([jasmine.any(EntityModel), entityModel]);
            });
        });

        describe('init model with raw data with includes', function() {
            var getEntityRelationshipCollection;
            var entityModel;
            var rawData = {
                data: {
                    type: 'tasks',
                    id: '12',
                    attributes: {
                        subject: 'Wake up'
                    },
                    relationships: {
                        owner: {
                            data: {
                                type: 'users',
                                id: '1'
                            }
                        },
                        organization: {
                            data: {
                                type: 'organizations',
                                id: '1'
                            }
                        }
                    },
                    meta: {
                        title: 'Wake up (John Doe)'
                    }
                },
                included: [
                    {
                        type: 'users',
                        id: '1',
                        attributes: {
                            title: 'John',
                            username: 'admin'
                        },
                        relationships: {
                            organization: {
                                data: {
                                    type: 'organizations',
                                    id: '1'
                                }
                            }
                        }
                    },
                    {
                        type: 'organizations',
                        id: '1',
                        attributes: {
                            name: 'OroCRM'
                        },
                        relationships: {
                            users: {
                                data: [
                                    {
                                        type: 'users',
                                        id: '1'
                                    },
                                    {
                                        type: 'users',
                                        id: '2'
                                    }
                                ]
                            }
                        }
                    }
                ]
            };

            beforeEach(function() {
                spyOn(EntityModel, 'getEntityModel').and.callThrough();
                getEntityRelationshipCollection = jasmine.createSpy('getEntityRelationshipCollection').and
                    .callFake(function(params, applicant) {
                        return new Backbone.Collection(params.data);
                    });
                exposure.substitute('mediator').by({
                    execute: function(name, params, applicant) {
                        if (name === 'getEntityRelationshipCollection') {
                            return getEntityRelationshipCollection(params, applicant);
                        }
                    }
                });

                entityModel = new EntityModel(rawData);
            });

            afterEach(function() {
                exposure.recover('mediator');
            });

            it('init relationships', function() {
                expect(EntityModel.getEntityModel.calls.count()).toBe(3);
                expect(getEntityRelationshipCollection.calls.count()).toBe(1);
                expect(EntityModel.getEntityModel.calls.argsFor(0))
                    .toEqual([{
                        data: {
                            type: 'users',
                            id: '1',
                            attributes: {
                                title: 'John',
                                username: 'admin'
                            },
                            relationships: {
                                organization: {
                                    data: {type: 'organizations', id: '1'}
                                }
                            }
                        }
                    }, entityModel]);

                expect(EntityModel.getEntityModel.calls.argsFor(1))
                    .toEqual([{
                        data: {
                            type: 'organizations',
                            id: '1'
                        }
                    }, jasmine.any(EntityModel)]);

                expect(EntityModel.getEntityModel.calls.argsFor(2))
                    .toEqual([{
                        data: {
                            type: 'organizations',
                            id: '1',
                            attributes: {
                                name: 'OroCRM'
                            },
                            relationships: {
                                users: {
                                    data: [
                                        {type: 'users', id: '1'},
                                        {type: 'users', id: '2'}
                                    ]
                                }
                            }
                        }
                    }, jasmine.any(EntityModel)]);

                expect(getEntityRelationshipCollection.calls.argsFor(0))
                    .toEqual([{
                        association: 'users',
                        type: 'organizations',
                        id: '1',
                        data: [
                            {type: 'users', id: '1'},
                            {type: 'users', id: '2'}
                        ]
                    }, jasmine.any(EntityModel)]);
            });

            it('serialize attributes', function() {
                var data = entityModel.serialize();
                expect(data).toEqual({});
                expect(Object.getPrototypeOf(data)).toEqual({
                    type: 'tasks',
                    id: '12',
                    subject: 'Wake up',
                    owner: {
                        type: 'users',
                        id: '1',
                        title: 'John',
                        username: 'admin',
                        organization: {
                            type: 'organizations',
                            id: '1',
                            name: 'OroCRM',
                            users: [{
                                type: 'users',
                                id: '1'
                            }, {
                                type: 'users',
                                id: '2'
                            }]
                        }
                    },
                    organization: {
                        type: 'organizations',
                        id: '1',
                        name: 'OroCRM',
                        users: [{
                            type: 'users',
                            id: '1'
                        }, {
                            type: 'users',
                            id: '2'
                        }]
                    },
                    toString: jasmine.any(Function)
                });
                expect(String(data)).toBe(rawData.data.meta.title);
            });

            it('convert model to JSON', function() {
                expect(entityModel.toJSON()).toEqual({
                    data: {
                        id: '12',
                        type: 'tasks',
                        attributes: {
                            subject: 'Wake up'
                        },
                        relationships: {
                            owner: {
                                data: {
                                    type: 'users',
                                    id: '1'
                                }
                            },
                            organization: {
                                data: {
                                    type: 'organizations',
                                    id: '1'
                                }
                            }
                        }
                    }
                });
            });
        });
    });
});
