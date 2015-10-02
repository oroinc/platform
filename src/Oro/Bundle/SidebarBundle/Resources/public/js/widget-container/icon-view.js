define(function(require) {
    'use strict';

    var WidgetContainerIconView;
    var Backbone = require('backbone');
    var iconTemplate = require('tpl!./templates/icon-template.html');
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetContainerIconView = BaseView.extend({
        className: 'sidebar-icon',
        template: iconTemplate,

        events: {
            'click': 'onClick'
        },

        listen: {
            'change model': 'render'
        },

        render: function() {
            WidgetContainerIconView.__super__.render.call(this);

            this.$el.attr('data-cid', this.model.cid);
            this.$el.toggleClass('sidebar-highlight', this.model.get('highlighted'));

            return this;
        },

        onClick: function(e) {
            e.stopPropagation();
            e.preventDefault();

            if (this.model.isDragged) {
                return;
            }

            var cord = this.$el.offset();

            Backbone.trigger('showWidgetHover', this.model.cid, cord);
        }
    });

    return WidgetContainerIconView;
});
