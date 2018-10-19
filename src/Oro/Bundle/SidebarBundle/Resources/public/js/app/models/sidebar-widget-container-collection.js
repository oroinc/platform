define(function(require) {
    'use strict';

    var SidebarWidgetContainerCollection;
    var _ = require('underscore');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var SidebarWidgetContainerModel = require('orosidebar/js/app/models/sidebar-widget-container-model');

    SidebarWidgetContainerCollection = BaseCollection.extend({
        model: SidebarWidgetContainerModel,

        comparator: 'position',

        /**
         * @inheritDoc
         */
        constructor: function SidebarWidgetContainerCollection() {
            SidebarWidgetContainerCollection.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(data, options) {
            _.extend(this, _.pick(options, ['url']));
            SidebarWidgetContainerCollection.__super__.initialize.apply(this, arguments);
        }
    });

    return SidebarWidgetContainerCollection;
});
