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
         * Adds applicant relation to registry for the instance
         *
         * @param {{globalId: string}} instance
         * @param {RegistryApplicant} applicant
         */
        retain: function(instance, applicant) {
            var obj = this.fetch(instance.globalId, applicant);
            if (!obj) {
                this.put(instance, applicant);
            }
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
         * Fetches instance from registry by globalId
         *
         * @param {string} globalId
         * @param {RegistryApplicant} applicant
         * @return {Object|null}
         */
        fetch: function(globalId, applicant) {
            var entry = this._entries[globalId];
            if (entry) {
                entry.addApplicant(applicant);
            }
            return entry ? entry.instance : null;
        },

        /**
         * Puts the instance with related applicant to the registry
         *
         * @param {{globalId: string}} instance
         * @param {RegistryApplicant} applicant
         * @throws {Error} invalid instance or instance already exists in registry
         */
        put: function(instance, applicant) {
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
                this._removeEntry(entry);
            });
            this.listenTo(entry, 'removeApplicant', function(entry) {
                var instance = entry.instance;
                if (!entry.applicants.length || !this._hasExternalRequester(entry)) {
                    this._removeEntry(entry);
                    instance.dispose();
                }
            });
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
         * @protected
         */
        _removeEntry: function(entry) {
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
