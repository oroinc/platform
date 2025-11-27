import Backbone from 'backbone';
import Registry from 'oroui/js/app/services/registry/registry';

describe('oroui/js/app/services/registry/registry', function() {
    let registry;
    let applicant1;
    let applicant2;
    let applicant3;
    let instance1;
    let instance2;
    let instance3;

    beforeEach(function() {
        instance1 = applicant1 = Object.create(Backbone.Events);
        instance2 = applicant2 = Object.create(Backbone.Events);
        instance3 = applicant3 = Object.create(Backbone.Events);
        instance1.globalId = 'instance:1';
        instance2.globalId = 'instance:2';
        instance3.globalId = 'instance:3';
        instance1.dispose = jasmine.createSpy('dispose1');
        instance2.dispose = jasmine.createSpy('dispose2');
        instance3.dispose = jasmine.createSpy('dispose3');

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

        const entry = registry._entries[instance1.globalId];
        expect(entry).toBeDefined();
        expect(entry.instance).toBe(instance1);
        expect(entry.applicants).toContain(applicant2);
    });

    it('register instance over retain method', function() {
        registry.retain(instance1, applicant2);

        const entry = registry._entries[instance1.globalId];
        expect(entry).toBeDefined();
        expect(entry.instance).toBe(instance1);
        expect(entry.applicants).toContain(applicant2);
    });

    it('fetch instance from registry', function() {
        const instanceA = registry.fetch(instance1.globalId, applicant2);
        expect(instanceA).toBe(null);

        registry.put(instance1, applicant2);

        const instanceB = registry.fetch(instance1.globalId, applicant3);
        expect(instanceB).toBe(instance1);

        const entry = registry._entries[instance1.globalId];
        expect(entry.applicants).toContain(applicant2);
        expect(entry.applicants).toContain(applicant3);
    });

    it('relieve an instance action removes applicant', function() {
        registry.put(instance1, applicant2);
        const entry = registry._entries[instance1.globalId];

        registry.relieve(instance1, applicant2);

        expect(entry.applicants).not.toContain(applicant2);
    });

    it('dispose instance removes entry and disposes entry object', function() {
        registry.put(instance1, applicant2);
        const entry = registry._entries[instance1.globalId];
        spyOn(entry, 'dispose').and.callThrough();

        instance1.trigger('dispose', instance1);

        expect(entry.dispose).toHaveBeenCalled();
        expect(registry._entries[instance1.globalId]).toBeUndefined();
    });

    it('remove last applicant action disposes entry and disposes instance', function() {
        registry.put(instance1, applicant2);
        const entry = registry._entries[instance1.globalId];
        spyOn(entry, 'dispose').and.callThrough();

        registry.retain(instance1, applicant3);
        expect(entry.applicants).toEqual(jasmine.arrayContaining([applicant2, applicant3]));

        registry.relieve(instance1, applicant2);
        expect(entry.applicants).toContain(applicant3);
        expect(instance1.dispose).not.toHaveBeenCalled();
        expect(entry.dispose).not.toHaveBeenCalled();

        registry.relieve(instance1, applicant3);
        expect(instance1.dispose).toHaveBeenCalled();
        expect(entry.dispose).toHaveBeenCalled();
        expect(registry._entries[instance1.globalId]).toBeUndefined();
    });

    it('remove last external applicant action disposes entry and disposes instance', function() {
        registry.put(instance1, applicant2);
        const entry1 = registry._entries[instance1.globalId];
        spyOn(entry1, 'dispose').and.callThrough();

        registry.retain(instance1, instance3);

        registry.relieve(instance1, applicant2);
        expect(instance1.dispose).not.toHaveBeenCalled();
        expect(entry1.dispose).not.toHaveBeenCalled();

        registry.retain(instance1, applicant2);
        registry.retain(instance3, instance1);
        registry.relieve(instance1, applicant2);

        expect(instance1.dispose).toHaveBeenCalled();
        expect(entry1.dispose).toHaveBeenCalled();
        expect(registry._entries[instance1.globalId]).toBeUndefined();
    });
});
