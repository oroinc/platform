define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const BaseModel = require('./model');
    const HistoryModel = require('../history-model');
    const HistoryStateModel = require('../history-state-model');

    const StatefulModel = BaseModel.extend({
        /**
         * @inheritdoc
         */
        constructor: function StatefulModel(attrs, options) {
            StatefulModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(attrs, options) {
            const historyOptions = {};
            StatefulModel.__super__.initialize.call(this, attrs, options);
            if ('MAX_HISTORY_LENGTH' in this) {
                historyOptions.maxLength = this.MAX_HISTORY_LENGTH;
            }
            this.history = new HistoryModel({}, historyOptions);
            this.boundEvents = [];
            this.debouncedOnStateChange = _.debounce(this.onStateChange.bind(this), 50);
            this.bindEvents();
        },

        destroy: function() {
            StatefulModel.__super__.destroy.call(this);
            this.unbindEvents();
        },

        bindEvents: function() {
            if ('observedAttributes' in this) {
                _.each(this.observedAttributes, function(attrName) {
                    const prop = this.get(attrName);
                    if (prop instanceof Backbone.Collection) {
                        const eventName = 'change add remove';
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
                    const prop = this.get(attrName);
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
            const state = new HistoryStateModel({
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

