/*global define*/
define(['jquery', 'underscore', 'backbone', 'routing', 'bootstrap'
    ], function ($, _, Backbone, routing) {
    'use strict';


    var mediator = require('oroui/js/mediator');
    /**
     * @export  oronavigation/js/shortcuts/view
     * @class   oronavigation.shortcuts.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            el: '.shortcuts .input-large',
            source: null
        },

        events: {
            'change': 'onChange'
        },

        data: {},

        cache: {},

        initialize: function(options) {
            var self = this;
            this.options = _.defaults(options || {}, this.options);

            this.$body = jQuery('.shortcuts');
            this.$el.val('');
            this.$el.typeahead({
                source:_.bind(this.source, this)
            });
            this.$form = this.$el.closest('form');
            this.render();
        },

        source: function(query, process) {
            if (_.isArray(this.options.source)) {
                process(this.options.source);
                mediator.execute('layout:init', this.$body, this);
            } else if (!_.isUndefined(this.cache[query])) {
                process(this.cache[query]);
                mediator.execute('layout:init', this.$body, this);
            } else {
                var url = routing.generate(this.options.source, { 'query': query });
                $.get(url, _.bind(function(data) {
                    //console.log('data', data);
                    this.data = data;
                    var result = [];
                    _.each(data, function(item, key) {
                        result.push({
                            key: key,
                            item: item
                        });
                    });
                    this.cache[query] = result;
                    process(result);
                    mediator.execute('layout:init', this.$body, this);
                }, this));
            }
        },

        onChange: function() {
            var key = this.$el.val(),
                dataItem;
            this.$el.val('');
            if (!_.isUndefined(this.data[key])) {
                dataItem = this.data[key];
                this.$form.attr("action", dataItem.url).submit();
            }
        },

        render: function() {
            mediator.execute('layout:init', this.$body, this);
            return this;
        }
    });
});
