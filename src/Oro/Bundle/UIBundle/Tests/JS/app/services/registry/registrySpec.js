define(function(require) {
    'use strict';

    var Backbone = require('backbone');
    var exposure = require('requirejs-exposure')
        .disclose('oroui/js/app/services/registry/registry');
    var EntityRegistry = require('oroui/js/app/services/registry/registry');

    describe('oroui/js/app/services/registry/registry', function() {

        var registry;
        var applicant1;
        var applicant2;
        var applicant3;
        var instance1;
        var instance2;
        var instance3;
        var MockRegistryEntry;

        beforeEach(function() {
            registry = new EntityRegistry();
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
                var entry = Object.create(Backbone.Events);
                entry.instance = instance;
                entry.applicants = [];
                entry.addApplicant = function(applicant) {
                    this.applicants.push(applicant);
                };
                entry.removeApplicant = function(applicant) {
                    var index = this.applicants.indexOf(applicant);
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

            exposure.substitute('RegistryEntry').by(MockRegistryEntry);
        });

        afterEach(function() {
            exposure.recover('RegistryEntry');
        });

        it('implements Backbone.Events', function() {
            expect(Object.getPrototypeOf(EntityRegistry.prototype)).toBe(Backbone.Events);
        });

        it('throw error on invalid instance', function() {
            expect(function() {
                registry.registerInstance({}, applicant2);
            }).toThrowError(/globalId/);
        });

        it('throw error on attempt to register existing instance', function() {
            expect(function() {
                registry.registerInstance(instance1, applicant2);
            }).not.toThrowError();

            expect(function() {
                registry.registerInstance(instance1, applicant3);
            }).toThrowError(/(globalId)[\w\W]+(is already registered)/);
        });

        it('register instance', function() {
            var entry = registry.registerInstance(instance1, applicant2);
            expect(MockRegistryEntry).toHaveBeenCalledWith(instance1);
            expect(entry.addApplicant).toHaveBeenCalledWith(applicant2);
        });

        it('register instance over retain method', function() {
            var entry = registry.retain(instance1, applicant2);
            expect(MockRegistryEntry).toHaveBeenCalledWith(instance1);
            expect(entry.addApplicant).toHaveBeenCalledWith(applicant2);
        });

        it('get entry from registry', function() {
            var entry1 = registry.getEntry(instance1.globalId, applicant2);
            expect(entry1).toBe(undefined);

            entry1 = registry.registerInstance(instance1, applicant2);
            expect(MockRegistryEntry).toHaveBeenCalledWith(instance1);

            var entry2 = registry.getEntry(instance1.globalId, applicant3);
            expect(entry2).toBe(entry1);

            var entry3 = registry.retain(instance1, applicant3);
            expect(entry3).toBe(entry1);
            expect(MockRegistryEntry.calls.count()).toBe(1);
        });

        it('remove entry from registry', function() {
            var entry = registry.registerInstance(instance1, applicant2);

            var entry1 = registry.getEntry(instance1.globalId, applicant2);
            expect(entry1).not.toBe(undefined);

            registry.removeEntry(entry);
            expect(entry.dispose).toHaveBeenCalled();

            var entry2 = registry.getEntry(instance1.globalId, applicant2);
            expect(entry2).toBe(undefined);
        });

        it('relieve an instance action removes applicant', function() {
            var entry = registry.registerInstance(instance1, applicant2);

            registry.relieve(instance1, applicant2);
            expect(entry.removeApplicant).toHaveBeenCalledWith(applicant2);
        });

        it('dispose instance', function() {
            var entry = registry.registerInstance(instance1, applicant2);
            instance1.trigger('dispose', instance1);
            expect(entry.dispose).toHaveBeenCalled();
        });

        it('remove last applicant action disposes entry and disposes instance', function() {
            var entry = registry.registerInstance(instance1, applicant2);
            registry.retain(instance1, applicant3);

            entry.removeApplicant(applicant2);
            expect(instance1.dispose).not.toHaveBeenCalled();
            expect(entry.dispose).not.toHaveBeenCalled();

            entry.removeApplicant(applicant3);
            expect(instance1.dispose).toHaveBeenCalled();
            expect(entry.dispose).toHaveBeenCalled();
        });

        it('remove last external applicant action disposes entry and disposes instance', function() {
            var entry = registry.registerInstance(instance1, applicant2);
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
