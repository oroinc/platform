import _ from 'underscore';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import TransitionsShortListView from '../transition/transition-list-short-view';
import StepModel from '../../models/step-model';

const StepRowView = BaseView.extend({
    tagName: 'tr',

    events: {
        'click .edit-step': 'triggerEditStep',
        'click .delete-step': 'triggerRemoveStep',
        'click .clone-step': 'triggerCloneStep',
        'click .add-step-transition': 'triggerAddStepTransition'
    },

    options: {
        workflow: null,
        template: null
    },

    listen: {
        'destroy model': 'remove'
    },
    /**
     * @inheritdoc
     */
    constructor: function StepRowView(options) {
        StepRowView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        const template = this.options.template || $('#step-row-template').html();
        this.template = _.template(template);
        this.transitionsListView = null;
    },

    triggerEditStep: function(e) {
        e.preventDefault();
        this.options.workflow.trigger('requestEditStep', this.model);
    },

    triggerRemoveStep: function(e) {
        e.preventDefault();
        this.options.workflow.trigger('requestRemoveStep', this.model);
    },

    triggerCloneStep: function(e) {
        e.preventDefault();
        this.options.workflow.trigger('requestCloneStep', this.model);
    },

    triggerAddStepTransition: function(e) {
        e.preventDefault();
        this.options.workflow.trigger('requestAddTransition', this.model);
    },

    remove: function() {
        if (this.transitionsListView) {
            this.transitionsListView.remove();
        }
        StepRowView.__super__.remove.call(this);
    },

    render: function() {
        if (!(this.model instanceof StepModel)) {
            throw new Error('Model must be an instance of StepModel');
        }
        this.transitionsListView = new TransitionsShortListView({
            collection: this.model.getAllowedTransitions(this.options.workflow),
            workflow: this.options.workflow,
            stepFrom: this.model
        });
        const rowHtml = $(this.template(this.model.toJSON()));
        const transitionsListEl = this.transitionsListView.render().$el;
        rowHtml.filter('.step-transitions').append(transitionsListEl);
        this.$el.append(rowHtml);

        return this;
    }
});

export default StepRowView;
