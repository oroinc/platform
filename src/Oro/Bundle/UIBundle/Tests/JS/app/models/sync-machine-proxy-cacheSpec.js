import _ from 'underscore';
import Backbone from 'backbone';
import Chaplin from 'chaplin';
import persistentStorage from 'oroui/js/persistent-storage';
import errorHandler from 'oroui/js/error';
import SyncMachineProxyCache from 'oroui/js/app/models/sync-machine-proxy-cache';

describe('oroui/js/app/models/sync-machine-proxy-cache', function() {
    let instance;
    let storedData;
    let storageKey;
    let expireTime;

    beforeEach(function() {
        storedData = {};
        spyOn(persistentStorage, 'getItem')
            .and.callFake(function(key) {
                return storedData[key];
            });
        spyOn(persistentStorage, 'setItem')
            .and.callFake(function(key, value) {
                storedData[key] = value;
            });

        spyOn(errorHandler, 'showErrorInConsole');

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
            expect(errorHandler.showErrorInConsole).toHaveBeenCalledWith(jasmine.any(Error));
            expect(persistentStorage.getItem).not.toHaveBeenCalled();
        });

        it('missing SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME property', function() {
            delete instance.SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME;
            instance.ensureSync();
            expect(errorHandler.showErrorInConsole).toHaveBeenCalledWith(jasmine.any(Error));
            expect(persistentStorage.getItem).not.toHaveBeenCalled();
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
                expect(persistentStorage.getItem).not.toHaveBeenCalled();
            });
        });
    });

    describe('empty cache', function() {
        beforeEach(function() {
            instance.ensureSync();
        });

        it('attempt to read from empty cache', function() {
            expect(persistentStorage.getItem).toHaveBeenCalled();
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
                expect(persistentStorage.setItem).toHaveBeenCalledWith(storageKey, jasmine.any(String));
            });

            it('cached JSON string is well formatted', function() {
                expect(function() {
                    JSON.parse(persistentStorage.setItem.calls.mostRecent().args[1]);
                }).not.toThrowError();
            });

            it('cached JSON string corresponds to sync data', function() {
                const data = JSON.parse(persistentStorage.setItem.calls.mostRecent().args[1]);
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
            expect(persistentStorage.getItem).toHaveBeenCalled();
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
            expect(persistentStorage.getItem).toHaveBeenCalled();
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
                expect(persistentStorage.setItem).toHaveBeenCalledWith(storageKey, jasmine.any(String));
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
