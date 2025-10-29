import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';
import BaseCollection from 'oroui/js/app/models/base/collection';
import TabCollectionView from 'oroui/js/app/views/tab-collection-view';

const AttributeGroupTabsComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function AttributeGroupTabsComponent(options) {
        AttributeGroupTabsComponent.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} options
     */
    initialize: function(options) {
        const data = _.each(options.data, function(item) {
            item.uniqueId = _.uniqueId(item.id);
        });

        this.groups = new BaseCollection(data);

        const first = this.groups.first();
        first.set('active', true);
        this.triggerGroupChange(first, true);

        this.view = new TabCollectionView({
            el: options._sourceElement,
            animationDuration: 0,
            collection: this.groups
        });

        this.listenTo(this.groups, 'change', this.onGroupChange);
        this.listenTo(this.groups, 'select', this.onGroupChange);
        this.listenTo(this.groups, 'click', this.onGroupClick);
    },

    onGroupChange: function(model) {
        if (model.get('active') === true) {
            this.triggerGroupChange(model);
        }
    },

    onGroupClick: function(model) {
        this.triggerGroupClick(model);
    },

    /**
     * Triggers global event via mediator and pass params to listeners
     *
     * @param {Backbone.Model} model
     * @param {boolean} initialize
     */
    triggerGroupChange: function(model, initialize) {
        mediator.trigger('entity-config:attribute-group:changed', model, initialize);
    },

    /**
     * Triggers global event via mediator and pass params to listeners
     *
     * @param {Backbone.Model} model
     * @param {boolean} initialize
     */
    triggerGroupClick: function(model, initialize) {
        mediator.trigger('entity-config:attribute-group:click', model, initialize);
    },

    getGroupById: function(id) {
        return this.groups.find(model => model.get('id') === id);
    }
});

export default AttributeGroupTabsComponent;
