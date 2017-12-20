define(function(require) {
    'use strict';

    function RegistryMock() {
        this._entries = {};
        spyOn(this, 'fetch').and.callThrough();
        spyOn(this, 'retain').and.callThrough();
        spyOn(this, 'put').and.callThrough();
        this.relieve = jasmine.createSpy('relieve');
    }

    RegistryMock.prototype = {
        fetch: function(globalId, applicant) {
            return this._entries[globalId] ? this._entries[globalId].instance : null;
        },
        retain: function(instance, applicant) {
            if (!this._entries[instance.globalId]) {
                this.add(instance, applicant);
            }
        },
        put: function(instance, applicant) {
            var globalId = instance.globalId;
            if (this._entries[globalId]) {
                throw new Error('is already registered');
            }
            return (this._entries[globalId] = {instance: instance});
        }
    };

    return RegistryMock;
});
