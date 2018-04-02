define(function(require) {
    'use strict';

    var SyncMachineProxyCache;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var Chaplin = require('chaplin');
    var persistentStorage = require('oroui/js/persistent-storage');
    var errorHandler = require('oroui/js/error');

    SyncMachineProxyCache = _.extend({}, Chaplin.SyncMachine);
    SyncMachineProxyCache.__super__ = Chaplin.SyncMachine;

    /**
     * Mixin for Models and Collections
     * Extends Chaplin.SyncMachine and overloads ensureSync method
     * Utilizes persistentStorage to preserve data from previews session
     * Useful for time consuming API with rarely changing data
     *
     * Instance has to define two properties
     *  - `SYNC_MACHINE_PROXY_CACHE_STORAGE_KEY`
     *  - `SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME`
     *
     * Triggers 'proxy-cache:stale-data-in-use' event once actual data are loaded from server
     * and they different from restored once
     */
    SyncMachineProxyCache.ensureSync = function() {
        var storageKey = this.SYNC_MACHINE_PROXY_CACHE_STORAGE_KEY;
        var expireTime = this.SYNC_MACHINE_PROXY_CACHE_EXPIRE_TIME;
        var observer;
        var cache;
        var isModified = false;
        if (!storageKey || !expireTime) {
            errorHandler.showErrorInConsole(new Error('Improperly implemented SyncMachineProxyCache'));
        }

        if (this.isUnsynced() && storageKey && expireTime) {
            observer = Object.create(Backbone.Events);
            cache = persistentStorage.getItem(storageKey);
            try {
                cache = JSON.parse(cache);
            } catch (e) {
                // if data is not valid JSON, ignore it
                cache = void 0;
            }

            if (cache && (Date.now() - cache.time) < expireTime) {
                this.set(cache.data, {parse: true});
                this.fetch(); // fetch actual data in background
                this.markAsSynced(); // set flag that where's data ready to use
                observer.listenToOnce(this, 'update change', function() {
                    isModified = true;
                });
            }

            observer.listenToOnce(this, 'sync', function(instance, data) {
                observer.stopListening();
                persistentStorage.setItem(storageKey, JSON.stringify({
                    time: Date.now(),
                    data: data
                }));
                if (isModified) {
                    instance.trigger('proxy-cache:stale-data-in-use', instance);
                }
            });
        }

        return SyncMachineProxyCache.__super__.ensureSync.call(this);
    };

    /**
     * @export oroui/js/app/models/sync-machine-proxy-cache
     */
    return SyncMachineProxyCache;
});
