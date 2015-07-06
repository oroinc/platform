/* global define */
define(function(require) {
    'use strict';

    var WorkflowHistoryModel,
        BaseModel = require('oroui/js/app/models/base/model'),
        WorkflowHistoryStateCollection = require('./workflow-history-state-collection');
    WorkflowHistoryModel = BaseModel.extend({
        MAX_LENGTH: 20,
        defaults: {
            states: null,
            index: -1
        },
        initialize: function () {
            this.set('states', new WorkflowHistoryStateCollection());
            this.set('index', -1);
        },
        pushState: function (state) {
            var states = this.get('states'),
                index = this.get('index');
            if(states.length > index + 1) {
                states.reset(states.first(index + 1));
            }
            if(states.length >= this.MAX_LENGTH ) {
                states.reset(states.last(this.MAX_LENGTH - 1));
            }
            states.add(state);
            this.set('index', states.length - 1);
        },
        getCurrentState: function () {
            return this.get('states').at(this.get('index'));
        },
        back: function () {
            var index = this.get('index');
            if (index > 0) {
                this.set('index', index - 1);
                this.trigger('navigate');
                return true;
            }
        },
        forward: function () {
            var index = this.get('index');
            if (index + 1 < this.get('states').length) {
                this.set('index', index + 1);
                this.trigger('navigate');
                return true;
            }
        }
    });
    return WorkflowHistoryModel;
});
