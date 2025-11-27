import _ from 'underscore';
import $ from 'jquery';
import Backbone from 'backbone';
import TagCollection from './collection';
import template from 'tpl-loader!../templates/tag-list.html';

/**
 * @export  orotag/js/view
 * @class   orotag.View
 * @extends Backbone.View
 */
const TagView = Backbone.View.extend({
    options: {
        filter: null
    },

    /** @property */
    template,

    /**
     * @inheritdoc
     */
    constructor: function TagView(options) {
        TagView.__super__.constructor.call(this, options);
    },

    /**
     * Constructor
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this.collection = new TagCollection();
        this.listenTo(this.getCollection(), 'reset', this.render);
        this.listenTo(this, 'filter', this.render);

        this.$tagsHolder = this.$('.tags-holder');

        // process filter action binding
        this.$('.tag-sort-actions a').on('click', this.filter.bind(this));
    },

    /**
     * Filter collection proxy
     *
     * @returns {*}
     */
    filter: function(e) {
        const $el = $(e.target);

        // clear all active links
        $el.parents('ul').find('a.active').removeClass('active');
        // make current filter active
        $el.addClass('active');

        this.options.filter = $el.data('type');
        this.trigger('filter');

        return this;
    },

    /**
     * Get collection object
     *
     * @returns {*}
     */
    getCollection: function() {
        return this.collection;
    },

    /**
     * Render widget
     *
     * @returns {}
     */
    render: function() {
        const templateData = this.getCollection().getFilteredCollection(this.options.filter);
        templateData.options = this.options;
        this.$tagsHolder.html(this.template(templateData));
        return this;
    }
});

export default TagView;
