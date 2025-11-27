import Backbone from 'backbone';
import sync from 'orosync/js/sync';

describe('orosync/js/sync', function() {
    let service;
    let originBackboneCollection;
    let originBackboneModel;

    beforeEach(function() {
        originBackboneCollection = Backbone.Collection;
        originBackboneModel = Backbone.Model;
        Backbone.Collection = function() {
            this.on = jasmine.createSpy('collection.on');
            this.off = jasmine.createSpy('collection.off');
        };
        Backbone.Model = function(attrs = {}) {
            const {id} = attrs;
            if (id) {
                this.id = id;
            }
            this.set = jasmine.createSpy('model.set').and.returnValue(this);
            this.on = jasmine.createSpy('model.on').and.returnValue(this);
            this.url = jasmine.createSpy('model.url').and.returnValue('some/model/1');
        };
        service = jasmine.createSpyObj('service', ['subscribe', 'unsubscribe', 'connect']);
        service.on = jasmine.createSpy('service.on').and.returnValue(service);
        service.once = jasmine.createSpy('service.once').and.returnValue(service);
        service.off = jasmine.createSpy('service.off').and.returnValue(service);
    });

    afterEach(function() {
        Backbone.Collection = originBackboneCollection;
        Backbone.Model = originBackboneModel;
    });

    it('setup service', function() {
        expect(() => sync({})).toThrow();
        expect(() => sync(service)).not.toThrow();
    });

    describe('check sync\'s methods', function() {
        beforeEach(function() {
            sync(service);
        });

        describe('tracking changes', function() {
            it('of any object', function() {
                const obj = {};
                sync.keepRelevant(obj);
                expect(service.subscribe).not.toHaveBeenCalled();
                sync.stopTracking(obj);
                expect(service.unsubscribe).not.toHaveBeenCalled();
            });

            it('subscribe new model', function() {
                const model = new Backbone.Model();
                sync.keepRelevant(model);
                expect(service.subscribe).not.toHaveBeenCalled();
            });

            it('unsubscribe new model', function() {
                const model = new Backbone.Model();
                sync.keepRelevant(model);
                sync.stopTracking(model);
                expect(service.unsubscribe).not.toHaveBeenCalled();
            });

            it('subscribe existing model', function() {
                const model = new Backbone.Model({id: 1});
                sync.keepRelevant(model);
                expect(service.subscribe).toHaveBeenCalledWith(model.url(), jasmine.any(Function));
                expect(model.on).toHaveBeenCalledWith('remove', jasmine.any(Function));
                // same callback function event
                const setModelAttrsCallback = service.subscribe.calls.mostRecent().args[1];
                sync.keepRelevant(model);
                expect(service.subscribe.calls.mostRecent().args[1]).toBe(setModelAttrsCallback);
            });

            it('unsubscribe existing model', function() {
                const model = new Backbone.Model({id: 1});
                sync.keepRelevant(model);
                const setModelAttrsCallback = service.subscribe.calls.mostRecent().args[1];
                sync.stopTracking(model);
                expect(service.unsubscribe).toHaveBeenCalledWith(model.url(), setModelAttrsCallback);
            });

            describe('of Backbone.Collection', function() {
                let collection;
                beforeEach(function() {
                    collection = new Backbone.Collection();
                    collection.url = 'some/model';
                    collection.models = [
                        new Backbone.Model({id: 1}),
                        new Backbone.Model({id: 2}),
                        new Backbone.Model({id: 3})
                    ];
                });

                it('tracking collection changes', function() {
                    sync.keepRelevant(collection);
                    expect(service.subscribe.calls.count()).toEqual(collection.models.length);
                    expect(collection.on).toHaveBeenCalled();
                    sync.stopTracking(collection);
                    expect(service.unsubscribe.calls.count()).toEqual(collection.models.length);
                    expect(collection.off).toHaveBeenCalledWith(collection.on.calls.mostRecent().args[0]);
                });

                describe('consistency of handling events', function() {
                    let events;
                    beforeEach(function() {
                        sync.keepRelevant(collection);
                        events = collection.on.calls.mostRecent().args[0];
                    });

                    it('collection "add" event', function() {
                        expect(events.add).toEqual(jasmine.any(Function));
                        expect(service.subscribe.calls.count()).toEqual(3);
                        events.add(new Backbone.Model({id: 1}));
                        expect(service.subscribe.calls.count()).toEqual(4);
                    });

                    it('collection "error" event', function() {
                        expect(events.error).toEqual(jasmine.any(Function));
                        events.error(collection);
                        // remove subscription for each models
                        expect(service.unsubscribe.calls.count()).toEqual(collection.models.length);
                    });

                    it('collection "reset" event', function() {
                        const options = {previousModels: collection.models};
                        collection.models = [
                            new Backbone.Model({id: 4}),
                            new Backbone.Model({id: 5})
                        ];
                        service.subscribe.calls.reset();
                        expect(events.reset).toEqual(jasmine.any(Function));
                        events.reset(collection, options);
                        // remove subscription for each previous models
                        expect(service.unsubscribe.calls.count()).toEqual(options.previousModels.length);
                        // add subscription for each new models
                        expect(service.subscribe.calls.count()).toEqual(collection.models.length);
                    });
                });
            });
        });

        it('sync.reconnect', function() {
            sync.reconnect();
            expect(service.connect).toHaveBeenCalled();
        });
    });
});
