/* global define */
/** @exports HistoryComponent */
define(function (require) {
    'use strict';

    var HistoryComponent,
        BaseComponent = require('../components/base/component'),
        StatefulModel = require('../models/base/stateful-model'),
        HistoryView = require('../views/history-view'),
        HistoryModel = require('../models/history-model'),
        HistoryStateModel = require('../models/history-state-model');

    /**
     * Builds history controls for undo/redo capability.
     *
     * @class HistoryComponent
     * @augments BaseComponent
     */
    HistoryComponent = BaseComponent.extend(/** @lends HistoryComponent.prototype */{
        history: null,
        observedModel: null,
        /**
         * @inheritDoc
         */
        initialize: function (options) {
            if (options.observedModel instanceof StatefulModel === false) {
                throw new Error('Observed object should be instance of StatefulModel');
            }
            this.observedModel = options.observedModel;
            this.history = new HistoryModel();
            this.historyView = new HistoryView({
                model: this.history,
                el: options._sourceElement
            });
            this.observedModel.on('stateChange', _.debounce(this.onObservedModelChange, 50), this);
            this.history.on('navigateHistory', this.onHistoryNavigate, this);
        },

        onObservedModelChange: function () {
            var state = new HistoryStateModel({
                data: this.observedModel.getState()
            });
            this.history.pushState(state);
        },

        onHistoryNavigate: function () {
            var state = this.history.getCurrentState();
            this.observedModel.setState(state.get('data'));
        }

    });

    return HistoryComponent;
});

