define(function(require) {
    'use strict';

    var WidgetContainerIconView;
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetContainerIconView = BaseView.extend({
        template: require('tpl!orosidebar/templates/sidebar-widget-container/widget-container-icon.html'),

        listen: {
            'change model': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetContainerIconView(options) {
            WidgetContainerIconView.__super__.constructor.call(this, options);
        }
    });

    return WidgetContainerIconView;
});
