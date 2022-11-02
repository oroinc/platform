define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseCollection = require('oroui/js/app/models/base/collection');
    const SidebarWidgetContainerModel = require('orosidebar/js/app/models/sidebar-widget-container-model');

    const SidebarWidgetContainerCollection = BaseCollection.extend({
        model: SidebarWidgetContainerModel,

        comparator: 'position',

        /**
         * @inheritdoc
         */
        constructor: function SidebarWidgetContainerCollection(data, options) {
            SidebarWidgetContainerCollection.__super__.constructor.call(this, data, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(data, options) {
            _.extend(this, _.pick(options, ['url']));
            SidebarWidgetContainerCollection.__super__.initialize.call(this, data, options);
        }
    });

    return SidebarWidgetContainerCollection;
});
