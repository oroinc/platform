define(function(require) {
    'use strict';

    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const CapabilityGroupView = require('orouser/js/views/role/capability-group-view');

    /**
     * @export orouser/js/views/role-view
     */
    const RoleCapabilitiesView = BaseCollectionView.extend({
        animationDuration: 0,

        itemView: CapabilityGroupView,

        listen: {
            visibilityChange: 'onVisibilityChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function RoleCapabilitiesView(options) {
            RoleCapabilitiesView.__super__.constructor.call(this, options);
        },

        onVisibilityChange: function() {
            this.$el.toggleClass('role-capabilities_single-group', this.visibleItems.length === 1);
        }
    });

    return RoleCapabilitiesView;
});
