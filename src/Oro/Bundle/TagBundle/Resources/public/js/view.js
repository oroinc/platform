define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var Backbone = require('backbone');
    var TagCollection = require('./collection');

    /**
     * @export  orotag/js/view
     * @class   orotag.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            filter: null
        },

        /** @property */
        template: require('tpl!../templates/tag-list.html'),

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
            this.$('.tag-sort-actions a').click(_.bind(this.filter, this));
        },

        /**
         * Filter collection proxy
         *
         * @returns {*}
         */
        filter: function(e) {
            var $el = $(e.target);

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
            var templateData = this.getCollection().getFilteredCollection(this.options.filter);
            templateData.options = this.options;
            this.$tagsHolder.html(this.template(templateData));
            return this;
        }
    });
});
