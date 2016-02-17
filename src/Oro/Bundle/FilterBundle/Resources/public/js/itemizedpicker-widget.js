define([
    'jquery',
    'underscore',
    'jquery-ui'
], function($, _) {
    'use strict';

    $.widget('orofilter.itemizedPicker', {
        options: {
            title: 'Title',
            items: [],
            onSelect: $.noop,
            template: '#date-picker-itemized-content'
        },

        _create: function() {
            this.render();
            this._on({
                'click a': 'onSelect'
            });
        },

        onSelect: function(e) {
            this.options.onSelect(e.target.text);
        },

        render: function() {
            var template = _.template($(this.options.template).html());
            this.element.html(template({
                    title: this.options.title,
                    items: this.options.items
                }));
        }
    });
});
