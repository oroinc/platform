/** @exports HistoryNavigationComponent */
import BaseComponent from '../components/base/component';
import StatefulModel from '../models/base/stateful-model';
import HistoryNavigationView from '../views/history-navigation-view';

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

export default HistoryNavigationComponent;
