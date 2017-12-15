define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');

    /**
     *
     * @param {Object} instance
     * @param {string} instance.globalId
     * @constructor
     * @mixes {Backbone.Events}
     */
    function RegistryEntry(instance) {
        this.instance = instance;
        this.id = instance.globalId;
        this.applicants = [];
    }

    RegistryEntry.prototype = _.extend(Object.create(Backbone.Events), /** @lends RegistryEntry.prototype */ {
        disposed: false,

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.trigger('dispose', this);
            this.stopListening();
            delete this.instance;
            this.applicants = [];
            this.disposed = true;
            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
        },

        addApplicant: function(applicant) {
            if (!applicant || !_.isFunction(applicant.trigger)) {
                throw new TypeError('applicant object should implement Backbone.Events');
            }
            if (this.applicants.indexOf(applicant) === -1) {
                this.applicants.push(applicant);
                this.listenToOnce(applicant, 'dispose', this.removeApplicant);
            }
        },

        removeApplicant: function(applicant) {
            var index = this.applicants.indexOf(applicant);
            if (index !== -1) {
                this.applicants.splice(index, 1);
                this.trigger('removeApplicant', this, applicant);
            }
        }
    });

    return RegistryEntry;
});
