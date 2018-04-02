define(function(require) {
    'use strict';

    var HistoryModel;
    var MAX_LENGTH = 64;
    var _ = require('underscore');
    var BaseModel = require('oroui/js/app/models/base/model');
    var HistoryStateCollection = require('./history-state-collection');

    HistoryModel = BaseModel.extend({
        defaults: {
            states: null,
            index: -1
        },

        /**
         * @inheritDoc
         */
        constructor: function HistoryModel() {
            HistoryModel.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(attrs, options) {
            this.options = _.defaults({}, options, {
                maxLength: MAX_LENGTH
            });
            this.set('states', new HistoryStateCollection());
            this.set('index', -1);
        },

        pushState: function(state) {
            var states = this.get('states');
            var index = this.get('index');
            if (states.length > index + 1) {
                states.reset(states.first(index + 1));
            }
            if (states.length >= this.options.maxLength) {
                states.reset(states.last(this.options.maxLength - 1));
            }
            states.add(state);
            this.set('index', states.length - 1);
        },

        getCurrentState: function() {
            return this.get('states').at(this.get('index'));
        },

        setIndex: function(index) {
            if (index >= 0 && index < this.get('states').length) {
                this.set('index', index);
                return true;
            }
        }
    });
    return HistoryModel;
});
