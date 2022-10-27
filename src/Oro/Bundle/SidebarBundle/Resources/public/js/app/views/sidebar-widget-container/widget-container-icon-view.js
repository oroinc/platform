define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const WidgetContainerIconView = BaseView.extend({
        template: require('tpl-loader!orosidebar/templates/sidebar-widget-container/widget-container-icon.html'),

        listen: {
            'change model': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function WidgetContainerIconView(options) {
            WidgetContainerIconView.__super__.constructor.call(this, options);
        }
    });

    return WidgetContainerIconView;
});
