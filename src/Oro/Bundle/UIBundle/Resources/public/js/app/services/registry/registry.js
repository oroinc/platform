define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');
    var RegistryEntry = require('oroui/js/app/services/registry/registry-entry');

    /** @typedef {(BaseModel|BaseCollection|BaseView|BaseComponent|BaseClass)} RegistryApplicant */

    function Registry() {
        this._entries = {};
    }

    Registry.prototype = _.extend(Object.create(Backbone.Events), {
        _entries: null,

        /**
         * Defines custom access methods to registry entries
         *
         * @param {Object.<string, function>} methods
         * @mixes methods
         */
        declareAccessMethods: function(methods) {
            _.each(methods, function(method, name) {
                if (name in this) {
                    throw new Error('The registry already has `' + name + '` property');
                }
                this[name] = method;
            }, this);
        },

        /**
         * Adds applicant relation to registry entry of instance
         *
         * @param {{globalId: string}} instance
         * @param {RegistryApplicant} applicant
         * @return {RegistryEntry}
         */
        retain: function(instance, applicant) {
            var entry = this.getEntry(instance.globalId, applicant);
            if (!entry) {
                entry = this.registerInstance(instance, applicant);
            }
            return entry;
        },

        /**
         * Removes applicant relation from registry entry of instance
         *
         * @param {{globalId: string}} instance
         * @param {RegistryApplicant} applicant
         */
        relieve: function(instance, applicant) {
            var entry = this._entries[instance.globalId];
            if (entry) {
                entry.removeApplicant(applicant);
            }
        },

        /**
         * Fetches entry from registry by globalId
         *
         * @param {string} globalId
         * @param {RegistryApplicant} applicant
         * @return {RegistryEntry|undefined}
         */
        getEntry: function(globalId, applicant) {
            var entry = this._entries[globalId];
            if (entry) {
                entry.addApplicant(applicant);
            }
            return entry;
        },

        /**
         * Creates entry for an instance with related applicant and stores entry in the registry
         *
         * @param {{globalId: string}} instance
         * @param {RegistryApplicant} applicant
         * @return {RegistryEntry}
         */
        registerInstance: function(instance, applicant) {
            var globalId = instance.globalId;
            if (!globalId) {
                throw new Error('globalId "' + globalId + '" of instance have not to be empty');
            }
            if (this._entries[globalId]) {
                throw new Error('Instance with globalId "' + globalId + '" is already registered');
            }
            var entry = this._entries[globalId] = new RegistryEntry(instance);
            entry.addApplicant(applicant);
            this.listenToOnce(instance, 'dispose', function() {
                this.removeEntry(entry);
            });
            this.listenTo(entry, 'removeApplicant', function() {
                if (!entry.applicants.length || !this._hasExternalRequester(entry)) {
                    entry.instance.dispose();
                    this.removeEntry(entry);
                }
            });
            return entry;
        },

        /**
         * Check if there's any applicant that is not an entry.instance (means external applicant)
         *
         * @param {RegistryEntry} entry
         * @return {boolean}
         */
        _hasExternalRequester: function(entry) {
            var queue = [entry];
            var checked = [];
            var relatedEntry;
            var currentEntry;

            do {
                currentEntry = queue.pop();
                checked.push(currentEntry);
                for (var i = 0; currentEntry.applicants.length > i; i++) {
                    relatedEntry = this._entries[currentEntry.applicants[i].globalId];
                    if (!relatedEntry) {
                        return true;
                    } else if (checked.indexOf(relatedEntry) === -1 && queue.indexOf(relatedEntry) === -1) {
                        queue.push(relatedEntry);
                    }
                }
            } while (queue.length > 0);

            return false;
        },

        /**
         * Removes an entry from registry
         *
         * @param {RegistryEntry} entry
         */
        removeEntry: function(entry) {
            var globalId = _.findKey(this._entries, entry);
            this.stopListening(entry);
            entry.dispose();
            delete this._entries[globalId];
        }
    });

    /**
     * @export oroui/js/app/services/registry/registry
     */
    return Registry;
});
