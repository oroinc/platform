import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import StatefulModel from 'oroui/js/app/models/base/stateful-model';
import helper from 'oroworkflow/js/tools/workflow-helper';
import StepCollection from './step-collection';
import TransitionCollection from './transition-collection';
import TransitionDefinitionCollection from './transition-definition-collection';
import AttributeCollection from './attribute-collection';
import StepModel from './step-model';
import TransitionModel from './transition-model';
import TransitionDefinitionModel from './transition-definition-model';
import AttributeModel from './attribute-model';

const WorkflowModel = StatefulModel.extend({
    defaults: {
        name: '',
        label: '',
        entity: '',
        entity_attribute: 'entity',
        start_step: null,
        steps_display_ordered: false,
        steps: null,
        transitions: null,
        transition_definitions: null,
        attributes: null,
        translateLinkLabel: null,
        priority: 0,
        exclusive_active_groups: [],
        exclusive_record_groups: [],
        applications: []
    },

    MAX_HISTORY_LENGTH: 50,

    observedAttributes: ['steps', 'transitions'],
    availableDestinations: {},

    positionIncrementPx: 35,

    /**
     * @inheritdoc
     */
    constructor: function WorkflowModel(attrs, options) {
        WorkflowModel.__super__.constructor.call(this, attrs, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(attrs, options) {
        if (this.get('steps') === null) {
            this.set('steps', new StepCollection());
        }
        if (this.get('transitions') === null) {
            this.set('transitions', new TransitionCollection());
        }
        if (this.get('transition_definitions') === null) {
            this.set('transition_definitions', new TransitionDefinitionCollection());
        }
        if (this.get('attributes') === null) {
            this.set('attributes', new AttributeCollection());
        }
        const steps = this.get('steps');
        const transitions = this.get('transitions');
        this.setWorkflowToCollection(steps);
        this.setWorkflowToCollection(transitions);
        this.listenTo(steps, 'add', this.setWorkflow);
        this.listenTo(transitions, 'add', this.setWorkflow);
        this.listenTo(steps, 'reset', this.setWorkflowToCollection);
        this.listenTo(transitions, 'reset', this.setWorkflowToCollection);
        WorkflowModel.__super__.initialize.call(this, attrs, options);

        // Add an initial state to history for an already created workflow
        const startStep = this.getStartStep();
        if (startStep.length) {
            this.onStateChange();
        }
    },

    setWorkflow: function(item) {
        item.setWorkflow(this);
    },

    setWorkflowToCollection: function(collection) {
        collection.each(function(item) {
            item.setWorkflow(this);
        }, this);
    },

    cloneTransitionDefinition: function(definition) {
        if (_.isString(definition)) {
            definition = this.getTransitionDefinitionByName(definition);
        }
        const cloned = this._getClonedItem(definition);

        const clonedModel = new TransitionDefinitionModel(cloned);
        this.get('transition_definitions').add(clonedModel);

        return clonedModel;
    },

    cloneTransition: function(transition, doNotAddToCollection) {
        if (_.isString(transition)) {
            transition = this.getTransitionByName(transition);
        }

        const cloned = this._getClonedItem(transition);
        if (transition.get('transition_definition')) {
            const transitionDefinition = this.cloneTransitionDefinition(transition.get('transition_definition'));
            cloned.transition_definition = transitionDefinition.get('name');
        }

        cloned.frontend_options = helper.deepClone(cloned.frontend_options);
        cloned.form_options = helper.deepClone(cloned.form_options);
        cloned.label = __('Copy of') + ' ' + cloned.label;
        if (doNotAddToCollection) {
            cloned._is_clone = true;
        }

        const clonedModel = new TransitionModel(cloned);
        clonedModel.setWorkflow(this);

        if (!doNotAddToCollection) {
            this.get('transitions').add(clonedModel);
        }

        return clonedModel;
    },

    cloneStep: function(step, doNotAddToCollection) {
        if (_.isString(step)) {
            step = this.getStepByName(step);
        }

        const cloned = this._getClonedItem(step);
        const clonedAllowedTransitions = [];
        _.each(step.getAllowedTransitions(this).models, function(transition) {
            const clonedTransition = this.cloneTransition(transition);
            clonedAllowedTransitions.push(clonedTransition.get('name'));
        }, this);
        cloned.allowed_transitions = clonedAllowedTransitions;
        cloned.label = __('Copy of') + ' ' + cloned.label;
        if (doNotAddToCollection) {
            cloned._is_clone = true;
        }
        if (cloned.position) {
            cloned.position = [
                cloned.position[0] + this.positionIncrementPx,
                cloned.position[1] + this.positionIncrementPx
            ];
        }

        const clonedModel = new StepModel(cloned);
        clonedModel.setWorkflow(this);

        if (!doNotAddToCollection) {
            this.get('steps').add(clonedModel);
        }

        return clonedModel;
    },

    _getClonedItem: function(item) {
        const cloned = _.clone(item.toJSON());
        cloned.name += '_clone_' + helper.getRandomId();

        return cloned;
    },

    getAvailableDestinations: function() {
        return this.availableDestinations;
    },

    setAvailableDestinations: function(destinations) {
        this.availableDestinations = destinations;
    },

    getAttributeByPropertyPath: function(propertyPath) {
        const fullPropertyPath = this.get('entity_attribute') + '.' + propertyPath;
        return this.get('attributes').findWhere({property_path: fullPropertyPath});
    },

    ensureAttributeByPropertyPath: function(propertyPath) {
        const fullPropertyPath = this.get('entity_attribute') + '.' + propertyPath;
        let attribute = this.get('attributes').findWhere({property_path: fullPropertyPath});
        if (!attribute) {
            attribute = new AttributeModel({
                name: this.generateAttributeName(propertyPath),
                property_path: fullPropertyPath
            });
            this.get('attributes').add(attribute);
        }
    },

    generateAttributeName: function(propertyPath) {
        const fullPropertyPath = this.get('entity_attribute') + '.' + propertyPath;
        return fullPropertyPath.replace(/\./g, '_');
    },

    getAttributeByName: function(name) {
        return this._getByName('attributes', name);
    },

    getStepByName: function(name) {
        return this._getByName('steps', name);
    },

    getStartStep: function() {
        return this.get('steps').where({_is_start: true});
    },

    getTransitionByName: function(name) {
        return this._getByName('transitions', name);
    },

    getTransitionDefinitionByName: function(name) {
        return this._getByName('transition_definitions', name);
    },

    getStartTransitions: function() {
        return this.get('transitions').where({is_start: true});
    },

    _getByName: function(item, name) {
        return this.get(item).findWhere({name: name});
    },

    getState: function() {
        return {
            steps: this.get('steps').toJSON(),
            transitions: this.get('transitions').toJSON()
        };
    },
    setState: function(data) {
        if (data.steps && data.transitions) {
            this.get('steps').reset(data.steps);
            this.get('transitions').reset(data.transitions);
        }
    }
});

export default WorkflowModel;
