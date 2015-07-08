define(function (require) {
    'use strict';

    var StatefulModel,
        _ = require('underscore'),
        Backbone = require('backbone'),
        BaseModel = require('./model'),
        HistoryModel = require('../history-model'),
        HistoryStateModel = require('../history-state-model');

    StatefulModel = BaseModel.extend({
        initialize: function () {
            var historyOptions = {};
            StatefulModel.__super__.initialize.apply(this, arguments);
            if ('MAX_HISTORY_LENGTH' in this) {
                historyOptions.maxLength = this.MAX_HISTORY_LENGTH;
            }
            this.history = new HistoryModel({}, historyOptions);
            this.boundEvents = [];
            this.bindEvents();
        },

        destroy: function () {
            StatefulModel.__super__.destroy.apply(this, arguments);
            this.unbindEvents();
        },

        bindEvents: function () {
            var that = this,
                onStateChange = _.debounce(_.bind(that.onStateChange, this), 50);
            if ('observedAttributes' in that) {
                _.each(that.observedAttributes, function (attrName){
                    var boundEvent,
                        prop = that.get(attrName);
                    if (prop) {
                        if (prop instanceof Backbone.Collection) {
                            boundEvent = that.bindEvent(prop, 'change add remove', onStateChange);
                            that.on('change:' + attrName, function (model, collection) {
                                that.unbindEvent(boundEvent);
                                that.bindEvent(collection, 'change add remove', onStateChange);
                            })
                        } else {
                            that.bindEvent(that, 'change:' + attrName, onStateChange);
                        }
                    }
                });
            } else {
                this.bindEvent(this, 'change', onStateChange);
            }
        },

        unbindEvents: function () {
            _.each(this.boundEvents,  function (boundEvent) {
                boundEvent.object.off(boundEvent.event, boundEvent.handler);
            });
            this.boundEvents = [];
        },

        unbindEvent: function (boundEvent) {
            boundEvent.object.off(boundEvent.event, boundEvent.handler);
            this.boundEvents = _.without(this.boundEvents, boundEvent);
        },

        bindEvent: function (object, event, handler) {
            var boundEvent;
            object.on(event, handler);
            boundEvent = {
                object: object,
                event: event,
                handler: handler
            };
            this.boundEvents.push(boundEvent);
            return boundEvent;
        },

        onStateChange: function () {
            var state = new HistoryStateModel({
                data: this.getState()
            })
            this.history.pushState(state);
        },

        getState: function () {
            throw new Error('Method getState should be defined in a inherited class.');
        },
        setState: function () {
            throw new Error('Method getState should be defined in a inherited class.');
        }
    });

    return StatefulModel;
});

