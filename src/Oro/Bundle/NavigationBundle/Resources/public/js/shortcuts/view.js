/*global define*/
define(['jquery', 'underscore', 'backbone', 'routing', 'oroui/js/mediator', 'bootstrap'
    ], function ($, _, Backbone, routing, mediator) {
    'use strict';

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
                source:_.bind(this.source, this),
                matcher: function (item) {
                    return ~item.key.toLowerCase().indexOf(this.query.toLowerCase())
                },
                sorter: function (items) {
                    var beginswith = []
                        , caseSensitive = []
                        , caseInsensitive = []
                        , item;

                    while (item = items.shift()) {
                        if (!item.key.toLowerCase().indexOf(this.query.toLowerCase())) beginswith.push(item)
                        else if (~item.key.indexOf(this.query)) caseSensitive.push(item)
                        else caseInsensitive.push(item)
                    }

                    return beginswith.concat(caseSensitive, caseInsensitive)
                }
            });
            this.$form = this.$el.closest('form');
            this.render();
        },

        source: function(query, process) {
            var self = this;
            if (_.isArray(this.options.source)) {
                process(this.options.source);
                this.render();
            } else if (!_.isUndefined(this.cache[query])) {
                process(this.cache[query]);
                this.render();
            } else {
                var url = routing.generate(this.options.source, { 'query': query });
                $.get(url, _.bind(function(data) {
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
                    self.render();
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
