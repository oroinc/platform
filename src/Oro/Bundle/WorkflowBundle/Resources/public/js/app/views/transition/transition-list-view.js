import _ from 'underscore';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import TransitionRowView from './transition-row-view';

const TransitionsListView = BaseView.extend({
    options: {
        listElBodyEl: 'tbody',
        template: null,
        workflow: null,
        collection: null,
        stepFrom: null
    },

    /**
     * @inheritdoc
     */
    constructor: function TransitionsListView(options) {
        TransitionsListView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        const template = this.options.template || $('#transition-list-template').html();
        this.template = _.template(template);
        this.rowViews = [];

        this.$listEl = $(this.template());
        this.$listElBody = this.$listEl.find(this.options.listElBodyEl);
        this.$emptyMessage = this.$listElBody.find('.no-rows-message');
        this.$el.html(this.$listEl);

        this.listenTo(this.getCollection(), 'change', this.render);
        this.listenTo(this.getCollection(), 'add', this.render);
        this.listenTo(this.getCollection(), 'remove', this.render);
        this.listenTo(this.getCollection(), 'reset', this.addAllItems);
    },

    addItem: function(item) {
        const rowView = new TransitionRowView({
            model: item,
            workflow: this.options.workflow,
            stepFrom: this.options.stepFrom
        });
        this.rowViews.push(rowView);
        this.$listElBody.append(rowView.render().$el);
    },

    addAllItems: function(items) {
        _.each(items, this.addItem, this);
    },

    getCollection: function() {
        return this.options.collection;
    },

    remove: function() {
        this.resetView();
        TransitionsListView.__super__.remove.call(this);
    },

    resetView: function() {
        _.each(this.rowViews, function(rowView) {
            rowView.remove();
        });
        this.rowViews = [];
    },

    render: function() {
        if (this.getCollection().models.length) {
            this.$emptyMessage.hide();
            this.resetView();
            this.addAllItems(this.getCollection().models);
        } else {
            this.$emptyMessage.show();
        }

        return this;
    }
});

export default TransitionsListView;
