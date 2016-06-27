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
            if (this.model.get('changed')) {
                classes.push('changed');
            }
            return classes.join(' ');
        },
        template: _.template('<a href="#"><%= label %></a>'),
        listen: {
            'change:active model': 'updateClasses',
            'change:changed model': 'updateClasses'
        },
        events: {
            'click a': 'onSelect'
        },
        updateClasses: function() {
            this.$el[0].className = _.result(this, 'className');
        },
        onSelect: function() {
            this.model.set('active', true);
        }
    });

    return TabItemView;
});
