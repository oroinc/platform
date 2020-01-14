define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const Chaplin = require('chaplin');
    const syncMachineProxyCacheManagerModuleInjector = require('inject-loader!oroui/js/app/models/sync-machine-proxy-cache');

    describe('oroui/js/app/models/sync-machine-proxy-cache', function() {
        let instance;
        let storedData;
        let storageMock;
        let errorHandlerMock;
        let storageKey;
        let expireTime;
        let SyncMachineProxyCache;

        beforeEach(function() {
            storedData = {};
            storageMock = {
                getItem: function(key) {
                    return storedData[key];
                },
                setItem: function(key, value) {
                    storedData[key] = value;
                }
            };
            spyOn(storageMock, 'getItem').and.callThrough();
            spyOn(storageMock, 'setItem').and.callThrough();

            errorHandlerMock = jasmine.createSpyObj('errorHandler', ['showErrorInConsole']);

            SyncMachineProxyCache = syncMachineProxyCacheManagerModuleInjector({
                'oroui/js/persistent-storage': storageMock,
                'oroui/js/error': errorHandlerMock
            });

            instance = _.extend(Object.create(Backbone.Events), SyncMachineProxyCache);
            instance.SYNC_MACHINE_PROXY_CACHE_STORAGE_KEY = storageKey = 'test';
            instance.SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME = expireTime = 1000;
            instance.set = jasmine.createSpy('set');
            instance.fetch = jasmine.createSpy('fetch').and.callFake(function() {
                return {then: jasmine.createSpy('then')};
            });
            spyOn(instance, 'once').and.callThrough();
            spyOn(instance, 'trigger').and.callThrough();
        });

        afterEach(function() {
            storedData = {};
        });

        describe('improperly implemented SyncMachineProxyCache', function() {
            it('missing SYNC_MACHINE_PROXY_CACHE_STORAGE_KEY property', function() {
                delete instance.SYNC_MACHINE_PROXY_CACHE_STORAGE_KEY;
                instance.ensureSync();
                expect(errorHandlerMock.showErrorInConsole).toHaveBeenCalledWith(jasmine.any(Error));
                expect(storageMock.getItem).not.toHaveBeenCalled();
            });

            it('missing SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME property', function() {
                delete instance.SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME;
                instance.ensureSync();
                expect(errorHandlerMock.showErrorInConsole).toHaveBeenCalledWith(jasmine.any(Error));
                expect(storageMock.getItem).not.toHaveBeenCalled();
            });
        });

        describe('only unsynced instance reads from cache', function() {
            const cases = [
                [Chaplin.SyncMachine.SYNCING],
                [Chaplin.SyncMachine.SYNCED],
                [Chaplin.SyncMachine.STATE_CHANGE]
            ];

            cases.forEach(function(testCase) {
                it(testCase[0] + ' instance does not read from cache', function() {
                    instance._syncState = testCase[0];
                    instance.ensureSync();
                    expect(storageMock.getItem).not.toHaveBeenCalled();
                });
            });
        });

        describe('empty cache', function() {
            beforeEach(function() {
                instance.ensureSync();
            });

            it('attempt to read from empty cache', function() {
                expect(storageMock.getItem).toHaveBeenCalled();
            });

            it('no data set into instance from cache', function() {
                expect(instance.set).not.toHaveBeenCalled();
            });

            describe('handle load of remote data', function() {
                beforeEach(function(done) {
                    // simulate data sync action
                    setTimeout(function() {
                        instance.trigger('sync', instance, {foo: 'bar'});
                        done();
                    });
                });

                it('set sync data to cache as JSON string with proper key', function() {
                    expect(storageMock.setItem).toHaveBeenCalledWith(storageKey, jasmine.any(String));
                });

                it('cached JSON string is well formatted', function() {
                    expect(function() {
                        JSON.parse(storageMock.setItem.calls.mostRecent().args[1]);
                    }).not.toThrowError();
                });

                it('cached JSON string corresponds to sync data', function() {
                    const data = JSON.parse(storageMock.setItem.calls.mostRecent().args[1]);
                    expect(data).toEqual({
                        time: jasmine.any(Number),
                        data: {foo: 'bar'}
                    });
                });

                it('no event triggered about stale data in use', function() {
                    expect(instance.trigger).not.toHaveBeenCalledWith('proxy-cache:stale-data-in-use', instance);
                });
            });
        });

        describe('malformed data in cache', function() {
            beforeEach(function() {
                storedData[storageKey] = 'qee';
                instance.ensureSync();
            });

            it('attempt to read from malformed data from cache', function() {
                expect(storageMock.getItem).toHaveBeenCalled();
            });

            it('no data set into instance from cache', function() {
                expect(instance.set).not.toHaveBeenCalled();
            });
        });

        describe('expired data in cache', function() {
            beforeEach(function() {
                storedData[storageKey] = JSON.stringify({
                    time: Date.now() - expireTime * 2,
                    data: {foo: 'bar'}
                });
                instance.ensureSync();
            });

            it('attempt to read from expired data from cache', function() {
                expect(storageMock.getItem).toHaveBeenCalled();
            });

            it('no data set into instance from cache', function() {
                expect(instance.set).not.toHaveBeenCalled();
            });
        });

        describe('data in cache', function() {
            let cacheTimeMark;
            beforeEach(function() {
                cacheTimeMark = Date.now();
                storedData[storageKey] = JSON.stringify({
                    time: cacheTimeMark,
                    data: {foo: 'bar'}
                });
                instance.ensureSync();
            });

            it('data from cache is set into instance', function() {
                expect(instance.set).toHaveBeenCalledWith({foo: 'bar'}, {parse: true});
            });

            describe('handle load of same remote data', function() {
                beforeEach(function(done) {
                    // simulate data sync action
                    setTimeout(function() {
                        instance.trigger('sync', instance, {foo: 'bar'});
                        done();
                    }, 2);
                });

                it('time mark of data is updated in cache', function() {
                    const data = JSON.parse(storedData[storageKey]);
                    expect(storageMock.setItem).toHaveBeenCalledWith(storageKey, jasmine.any(String));
                    expect(data.time).toBeGreaterThan(cacheTimeMark);
                });

                it('no event triggered about stale data in use', function() {
                    expect(instance.trigger).not.toHaveBeenCalledWith('proxy-cache:stale-data-in-use', instance);
                });
            });

            describe('handle load of updated remote data', function() {
                const cases = [
                    ['added model', {
                        changes: {
                            added: [{}], // not empty list of added models
                            removed: [],
                            merged: [{
                                hasChanged: function() {
                                    return false;
                                }
                            }]
                        }
                    }],
                    ['removed model', {
                        changes: {
                            added: [],
                            removed: [{}], // not empty list of removed models
                            merged: [{
                                hasChanged: function() {
                                    return false;
                                }
                            }]
                        }
                    }],
                    ['updated model', {
                        changes: {
                            added: [],
                            removed: [],
                            merged: [{// some model has been changed
                                hasChanged: function() {
                                    return true;
                                }
                            }]
                        }
                    }]
                ];

                cases.forEach(function(testCase) {
                    describe(testCase[0], function() {
                        beforeEach(function(done) {
                            // simulate data sync action
                            setTimeout(function() {
                                // simulate update event of collection
                                instance.trigger('update', instance, testCase[1]);
                                instance.trigger('sync', instance, [{foo: 'diff'}]);
                                done();
                            });
                        });

                        it('triggered event about stale data in use', function() {
                            expect(instance.trigger).toHaveBeenCalledWith('proxy-cache:stale-data-in-use', instance);
                        });
                    });
                });
            });
        });
    });
});
