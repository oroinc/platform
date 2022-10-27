/** @exports HistoryNavigationComponent */
define(function(require) {
    'use strict';

    const BaseComponent = require('../components/base/component');
    const StatefulModel = require('../models/base/stateful-model');
    const HistoryNavigationView = require('../views/history-navigation-view');

    /**
     * Builds history controls for undo/redo capability.
     *
     * @class HistoryNavigationComponent
     * @augments BaseComponent
     */
    const HistoryNavigationComponent = BaseComponent.extend(/** @lends HistoryNavigationComponent.prototype */{
        history: null,

        observedModel: null,

        /**
         * @inheritdoc
         */
        constructor: function HistoryNavigationComponent(options) {
            HistoryNavigationComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (options.observedModel instanceof StatefulModel === false) {
                throw new Error('Observed object should be instance of StatefulModel');
            }
            this.observedModel = options.observedModel;
            this.historyView = new HistoryNavigationView({
                model: this.observedModel.history,
                el: options._sourceElement
            });
            this.historyView.on('navigate', this.onHistoryNavigate, this);
        },

        onHistoryNavigate: function(index) {
            const history = this.observedModel.history;
            if (history.setIndex(index)) {
                const state = history.getCurrentState();
                this.observedModel.setState(state.get('data'));
            }
        }

    });

    return HistoryNavigationComponent;
});

