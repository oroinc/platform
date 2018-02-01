define(function(require) {
    'use strict';

    var Backbone = require('backbone');
    var RegistryEntry = require('oroui/js/app/services/registry/registry-entry');

    describe('oroui/js/app/services/registry/registry-entry', function() {
        it('implements Backbone.Events', function() {
            expect(Object.getPrototypeOf(RegistryEntry.prototype)).toBe(Backbone.Events);
        });

        var entry;
        var instance;
        var applicant1;
        var applicant2;
        var applicant3;

        describe('registryEntry', function() {
            beforeEach(function() {
                instance = {globalId: 'test::1'};

                applicant1 = Object.create(Backbone.Events);
                applicant2 = Object.create(Backbone.Events);
                applicant3 = Object.create(Backbone.Events);

                entry = new RegistryEntry(instance);
                spyOn(entry, 'trigger');
                spyOn(entry, 'listenToOnce').and.callThrough();
                spyOn(entry, 'stopListening');
            });

            it('create', function() {
                expect(entry.instance).toBe(instance);
                expect(entry.id).toBe(instance.globalId);
                expect(entry.applicants).toEqual(jasmine.any(Array));
                expect(entry.applicants.length).toBe(0);
            });

            it('addApplicant', function() {
                entry.addApplicant(applicant1);
                expect(entry.applicants.length).toBe(1);
                expect(entry.applicants).toContain(applicant1);
                expect(entry.listenToOnce)
                    .toHaveBeenCalledWith(applicant1, 'dispose', jasmine.any(Function));

                entry.addApplicant(applicant1);
                expect(entry.applicants.length).toBe(1);

                entry.addApplicant(applicant2);
                expect(entry.applicants.length).toBe(2);
                expect(entry.applicants).toContain(applicant2);
                expect(entry.listenToOnce)
                    .toHaveBeenCalledWith(applicant2, 'dispose', jasmine.any(Function));

                entry.addApplicant(applicant2);
                expect(entry.applicants.length).toBe(2);

                entry.addApplicant(applicant3);
                expect(entry.applicants.length).toBe(3);
                expect(entry.applicants).toContain(applicant3);
                expect(entry.listenToOnce)
                    .toHaveBeenCalledWith(applicant3, 'dispose', jasmine.any(Function));

                expect(entry.listenToOnce.calls.count()).toEqual(3);
            });

            it('removeApplicant', function() {
                entry.addApplicant(applicant1);
                entry.addApplicant(applicant2);
                entry.addApplicant(applicant3);

                entry.removeApplicant(applicant2);
                expect(entry.applicants).not.toContain(applicant2);
                expect(entry.applicants.length).toBe(2);
                expect(entry.trigger).toHaveBeenCalledWith('removeApplicant', entry, applicant2);

                entry.removeApplicant(applicant1);
                entry.removeApplicant(applicant3);
                expect(entry.applicants.length).toBe(0);
            });

            it('dispose', function() {
                entry.dispose();
                expect(entry.disposed).toBe(true);
                expect(entry.instance).toBeUndefined();
                expect(entry.applicants).toEqual([]);
                expect(Object.isFrozen(entry)).toBe(true);
                expect(entry.trigger).toHaveBeenCalledWith('dispose', entry);
                expect(entry.stopListening).toHaveBeenCalled();

                entry.dispose();
                expect(entry.trigger.calls.count()).toEqual(1);
            });
        });
    });
});
