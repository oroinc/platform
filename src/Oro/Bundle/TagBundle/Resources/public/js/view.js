define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const Backbone = require('backbone');
    const TagCollection = require('./collection');

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
        template: require('tpl-loader!../templates/tag-list.html'),

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
            this.$('.tag-sort-actions a').click(this.filter.bind(this));
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

    return TagView;
});
