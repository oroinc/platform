define(function(require) {
    'use strict';

    var Backbone = require('backbone');

    function RegistryMock() {
        this._entries = {};
        spyOn(this, 'getEntry').and.callThrough();
        spyOn(this, 'registerInstance').and.callThrough();
        spyOn(this, 'getEntity').and.callThrough();
        spyOn(this, 'getEntityRelationshipCollection').and.callThrough();
        this.retain = jasmine.createSpy('retain');
        this.relieve = jasmine.createSpy('relieve');
    }

    RegistryMock.prototype = {
        getEntry: function(globalId, applicant) {
            return this._entries[globalId];
        },
        registerInstance: function(instance, applicant) {
            var globalId = instance.globalId;
            if (this._entries[globalId]) {
                throw new Error('is already registered');
            }
            return (this._entries[globalId] = {instance: instance});
        },
        getEntity: function(params, applicant) {
            var model;
            var globalId = params.data.type + '::' + params.data.id;
            var entry = this._entries[globalId];
            if (entry) {
                model  = entry.instance;
            } else {
                model = new Backbone.Model(params.data);
                model.identifier = {type: params.data.type, id: params.data.id};
                this._entries[globalId] = {instance: model};
            }
            return model;
        },
        getEntityRelationshipCollection: function(params, applicant) {
            return new Backbone.Collection(params.data);
        }
    };

    return RegistryMock;
});
