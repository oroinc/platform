define(function(require) {
    'use strict';

    var TabItemView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    TabItemView = BaseView.extend({
        tagName: 'li',
        className: function() {
            var classes = [];
            if (this.model.get('active')) {
                classes.push('active');
            }
            return classes.join(' ');
        },
        template: _.template('<a href="#"><%= label %></a>'),
        listen: {
            'change:active model': 'updateState'
        },
        events: {
            'click a': 'onSelect'
        },
        updateState: function() {
            this.$el.toggleClass('active', this.model.get('active'));
        },
        onSelect: function() {
            this.model.set('active', true);
        }
    });

    return TabItemView;
});
