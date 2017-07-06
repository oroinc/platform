define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('jquery-ui');

    $.widget('orofilter.itemizedPicker', {
        options: {
            title: 'Title',
            items: [],
            onSelect: $.noop,
            template: require('tpl!orofilter/templates/filter/date-picker.html')
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
            var template = this.options.template;
            this.element.html(template({
                    title: this.options.title,
                    items: this.options.items
                }));
        }
    });
});
