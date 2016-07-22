define(function(require) {
    'use strict';

    var CollapsibleFormRowView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery.select2');

    CollapsibleFormRowView = BaseView.extend({

        autoRender: true,

        events: {
            'click [data-name=collapse]': 'onCollapseToggle'
        },

        render: function() {
            if (this.$el.hasClass('collapsed')) {
                this.$('.controls').hide();
            }
            return this;
        },

        onCollapseToggle: function() {
            var $controls = this.$('.controls');
            if (this.$el.hasClass('collapsed')) {
                $controls.slideDown();
            } else {
                $controls.slideUp();
            }
            this.$el.toggleClass('collapsed');
        }
    });

    return CollapsibleFormRowView;
});
