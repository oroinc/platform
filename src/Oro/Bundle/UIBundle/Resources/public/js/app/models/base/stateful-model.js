define(function(require) {
    'use strict';

    var StatefulModel;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var BaseModel = require('./model');
    var HistoryModel = require('../history-model');
    var HistoryStateModel = require('../history-state-model');

    StatefulModel = BaseModel.extend({
        /**
         * @inheritDoc
         */
        constructor: function StatefulModel() {
            StatefulModel.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            var historyOptions = {};
            StatefulModel.__super__.initialize.apply(this, arguments);
            if ('MAX_HISTORY_LENGTH' in this) {
                historyOptions.maxLength = this.MAX_HISTORY_LENGTH;
            }
            this.history = new HistoryModel({}, historyOptions);
            this.boundEvents = [];
            this.debouncedOnStateChange = _.debounce(_.bind(this.onStateChange, this), 50);
            this.bindEvents();
        },

        destroy: function() {
            StatefulModel.__super__.destroy.apply(this, arguments);
            this.unbindEvents();
        },

        bindEvents: function() {
            if ('observedAttributes' in this) {
                _.each(this.observedAttributes, function(attrName) {
                    var prop = this.get(attrName);
                    if (prop instanceof Backbone.Collection) {
                        var eventName = 'change add remove';
                        prop.on(eventName, this.debouncedOnStateChange, this);
                        this.on('change:' + attrName, function(model, collection) {
                            prop.off(eventName, this.debouncedOnStateChange, this);
                            collection.on(eventName, this.debouncedOnStateChange, this);
                        }, this);
                    } else {
                        this.on('change:' + attrName, this.debouncedOnStateChange, this);
                    }
                }, this);
            } else {
                this.on('change', this.debouncedOnStateChange, this);
            }
        },

        unbindEvents: function() {
            if ('observedAttributes' in this) {
                _.each(this.observedAttributes, function(attrName) {
                    var prop = this.get(attrName);
                    if (prop instanceof Backbone.Collection) {
                        prop.off('change add remove', this.debouncedOnStateChange, this);
                        this.off('change:' + attrName, null, this);
                    } else {
                        this.off('change:' + attrName, this.debouncedOnStateChange, this);
                    }
                }, this);
            } else {
                this.off('change', this.debouncedOnStateChange, this);
            }
        },

        onStateChange: function() {
            var state = new HistoryStateModel({
                data: this.getState()
            });
            this.history.pushState(state);
        },

        getState: function() {
            throw new Error('Method getState should be defined in a inherited class.');
        },

        setState: function() {
            throw new Error('Method getState should be defined in a inherited class.');
        }
    });

    return StatefulModel;
});

