import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import TransitionsShortRowView from './transition-row-short-view';

const TransitionsShortListView = BaseView.extend({
    tagName: 'ul',

    attributes: {
        'class': 'transitions-list-short'
    },

    options: {
        collection: null,
        workflow: null,
        stepFrom: null
    },
    /**
     * @inheritdoc
     */
    constructor: function TransitionsShortListView(options) {
        TransitionsShortListView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this.rowViews = [];

        this.listenTo(this.getCollection(), 'add', this.addItem);
        this.listenTo(this.getCollection(), 'reset', this.addAllItems);
    },

    addItem: function(item) {
        const rowView = new TransitionsShortRowView({
            model: item,
            workflow: this.options.workflow,
            stepFrom: this.options.stepFrom
        });
        this.rowViews.push(rowView);
        this.$el.append(rowView.render().$el);
    },

    addAllItems: function(items) {
        _.each(items, this.addItem, this);
    },

    getCollection: function() {
        return this.options.collection;
    },

    remove: function() {
        this.resetView();
        TransitionsShortListView.__super__.remove.call(this);
    },

    resetView: function() {
        _.each(this.rowViews, function(rowView) {
            rowView.remove();
        });
    },

    render: function() {
        this.addAllItems(this.getCollection().models);

        return this;
    }
});

export default TransitionsShortListView;
