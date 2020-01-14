define(function(require) {
    'use strict';

    const Backbone = require('backbone');
    const registryModuleInjector = require('inject-loader!oroui/js/app/services/registry/registry');

    describe('oroui/js/app/services/registry/registry', function() {
        let Registry;
        let registry;
        let applicant1;
        let applicant2;
        let applicant3;
        let instance1;
        let instance2;
        let instance3;
        let MockRegistryEntry;

        beforeEach(function() {
            instance1 = applicant1 = Object.create(Backbone.Events);
            instance2 = applicant2 = Object.create(Backbone.Events);
            instance3 = applicant3 = Object.create(Backbone.Events);
            instance1.globalId = 'instance:1';
            instance2.globalId = 'instance:2';
            instance3.globalId = 'instance:3';
            instance1.dispose = jasmine.createSpy('dispose');
            instance2.dispose = jasmine.createSpy('dispose');
            instance3.dispose = jasmine.createSpy('dispose');

            function RegistryEntry(instance) {
                const entry = Object.create(Backbone.Events);
                entry.instance = instance;
                entry.applicants = [];
                entry.addApplicant = function(applicant) {
                    this.applicants.push(applicant);
                };
                entry.removeApplicant = function(applicant) {
                    const index = this.applicants.indexOf(applicant);
                    if (index !== -1) {
                        this.applicants.splice(index, 1);
                        this.trigger('removeApplicant', this);
                    }
                };
                entry.dispose = function() {
                    this.trigger('dispose', this);
                };
                spyOn(entry, 'addApplicant').and.callThrough();
                spyOn(entry, 'removeApplicant').and.callThrough();
                spyOn(entry, 'dispose').and.callThrough();
                return entry;
            }
            MockRegistryEntry = jasmine.createSpy('RegistryEntry', RegistryEntry).and.callThrough();

            Registry = registryModuleInjector({
                'oroui/js/app/services/registry/registry-entry': MockRegistryEntry
            });

            registry = new Registry();
        });

        it('implements Backbone.Events', function() {
            expect(Object.getPrototypeOf(Registry.prototype)).toBe(Backbone.Events);
        });

        it('throw error on invalid instance', function() {
            expect(function() {
                registry.put({}, applicant1);
            }).toThrowError(/globalId/);
        });

        it('throw error on attempt to register existing instance', function() {
            expect(function() {
                registry.put(instance1, applicant1);
            }).not.toThrowError();

            expect(function() {
                registry.put(instance1, applicant3);
            }).toThrowError(/(globalId)[\w\W]+(is already registered)/);
        });

        it('register instance', function() {
            registry.put(instance1, applicant2);
            expect(MockRegistryEntry).toHaveBeenCalledWith(instance1);
            const entry = MockRegistryEntry.calls.mostRecent().returnValue;
            expect(entry.addApplicant).toHaveBeenCalledWith(applicant2);
        });

        it('register instance over retain method', function() {
            registry.retain(instance1, applicant2);
            expect(MockRegistryEntry).toHaveBeenCalledWith(instance1);
            const entry = MockRegistryEntry.calls.mostRecent().returnValue;
            expect(entry.addApplicant).toHaveBeenCalledWith(applicant2);
        });

        it('fetch instance from registry', function() {
            const instanceA = registry.fetch(instance1.globalId, applicant2);
            expect(instanceA).toBe(null);

            registry.put(instance1, applicant2);
            expect(MockRegistryEntry).toHaveBeenCalledWith(instance1);

            const instanceB = registry.fetch(instance1.globalId, applicant3);
            expect(instanceB).toBe(instance1);
            expect(MockRegistryEntry.calls.count()).toBe(1);
        });

        it('relieve an instance action removes applicant', function() {
            registry.put(instance1, applicant2);
            const entry = MockRegistryEntry.calls.mostRecent().returnValue;
            registry.relieve(instance1, applicant2);
            expect(entry.removeApplicant).toHaveBeenCalledWith(applicant2);
        });

        it('dispose instance', function() {
            registry.put(instance1, applicant2);
            const entry = MockRegistryEntry.calls.mostRecent().returnValue;
            instance1.trigger('dispose', instance1);
            expect(entry.dispose).toHaveBeenCalled();
        });

        it('remove last applicant action disposes entry and disposes instance', function() {
            registry.put(instance1, applicant2);
            const entry = MockRegistryEntry.calls.mostRecent().returnValue;
            registry.retain(instance1, applicant3);

            entry.removeApplicant(applicant2);
            expect(instance1.dispose).not.toHaveBeenCalled();
            expect(entry.dispose).not.toHaveBeenCalled();

            entry.removeApplicant(applicant3);
            expect(instance1.dispose).toHaveBeenCalled();
            expect(entry.dispose).toHaveBeenCalled();
        });

        it('remove last external applicant action disposes entry and disposes instance', function() {
            registry.put(instance1, applicant2);
            const entry = MockRegistryEntry.calls.mostRecent().returnValue;
            registry.retain(instance1, instance3);

            entry.removeApplicant(applicant2);
            expect(instance1.dispose).not.toHaveBeenCalled();
            expect(entry.dispose).not.toHaveBeenCalled();

            registry.retain(instance1, applicant2);
            registry.retain(instance3, instance1);
            entry.removeApplicant(applicant2);
            expect(instance1.dispose).toHaveBeenCalled();
            expect(entry.dispose).toHaveBeenCalled();
        });
    });
});
