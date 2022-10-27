define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');

    let service;
    let subscriptions = [];

    /**
     * Oro.Synchronizer - saves provided sync service internally and
     * exists as base namespace for public methods
     *
     * @param {Object} serv service which provides backend synchronization
     * @param {Function} serv.subscribe
     * @param {Function} serv.unsubscribe
     * @returns Oro.Synchronizer
     *
     * @var {Function} sync protected shortcut for Oro.Synchronizer
     *
     * @export orosync/js/sync
     * @name   orosync.sync
     */
    function sync(serv) {
        if (!(_.isObject(serv) && _.isFunction(serv.subscribe) && _.isFunction(serv.unsubscribe))) {
            throw new Error('Synchronization service does not fit requirements');
        }
        service = serv;
        while (subscriptions.length) {
            service.subscribe(...subscriptions.shift());
        }
        return sync;
    }

    /**
     * Subscribes provided model on update event
     * @param {Backbone.Model} model
     */
    function subscribeModel(model) {
        if (model.id) {
            // saves bound function in order to have same callback in unsubscribeModel call
            model['[[SetCallback]]'] = (model['[[SetCallback]]'] || model.set.bind(model));
            sync.subscribe(_.result(model, 'url'), model['[[SetCallback]]']);
            model.on('remove', unsubscribeModel);
        }
    }

    /**
     * Removes subscription for a provided model
     * @param {Backbone.Model} model
     */
    function unsubscribeModel(model) {
        if (model.id) {
            const args = [_.result(model, 'url')];
            if (_.isFunction(model['[[SetCallback]]'])) {
                args.push(model['[[SetCallback]]']);
            }
            sync.unsubscribe.apply(service, args);
        }
    }

    const events = {
        add: subscribeModel,
        error: function(collection) {
            _.each(collection.models, unsubscribeModel);
        },
        reset: function(collection, options) {
            _.each(options.previousModels, function(model) {
                model.urlRoot = collection.url;
                unsubscribeModel(model);
            });
            _.each(collection.models, subscribeModel);
        }
    };

    /**
     * Establish connection with server and updates a provided object instantly
     *
     * @param {Backbone.Collection|Backbone.Model} obj
     * @returns {oro.sync}
     */
    sync.keepRelevant = function(obj) {
        if (obj instanceof Backbone.Collection) {
            _.each(obj.models, subscribeModel);
            obj.on(events);
        } else if (obj instanceof Backbone.Model) {
            subscribeModel(obj);
        }
        return this;
    };

    /**
     * Drops instant update connection for provided object
     *
     * @param {Backbone.Collection|Backbone.Model} obj
     * @returns {oro.sync}
     */
    sync.stopTracking = function(obj) {
        if (obj instanceof Backbone.Collection) {
            _.each(obj.models, unsubscribeModel);
            obj.off(events);
        } else if (obj instanceof Backbone.Model) {
            unsubscribeModel(obj);
        }
        return this;
    };

    /**
     * Makes service to give a try to connect to server
     */
    sync.reconnect = function() {
        service.connect();
    };

    /**
     * Subscribes to listening server messages of a channel
     *
     * Safe wrapper over service which provides server connection
     *
     * @param {string} channel name of a channel
     * @param {Function} callback
     */
    sync.subscribe = function(channel, callback) {
        const args = [channel, callback];
        if (service) {
            service.subscribe(...args);
        } else {
            subscriptions.push(args);
        }
    };

    /**
     * Unsubscribes from listening server messages of a channel
     *
     * Safe wrapper over service which provides server connection
     *
     * @param {string} channel name of a channel
     * @param {Function?} callback
     */
    sync.unsubscribe = function(channel, callback) {
        let cleaner;
        const args = [channel, callback];
        if (service) {
            service.unsubscribe(...args);
        } else {
            cleaner = !callback
                ? function(args) {
                    return channel === args[0];
                }
                : function(args) {
                    return channel === args[0] && callback === args[1];
                };
            subscriptions = _.reject(subscriptions, cleaner);
        }
    };

    return sync;
});
