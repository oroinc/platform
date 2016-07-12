define(function(require) {
    'use strict';

    var RoleCapabilitiesView;
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var CapabilityGroupView = require('orouser/js/views/role/capability-group-view');

    /**
     * @export orouser/js/views/role-view
     */
    RoleCapabilitiesView = BaseCollectionView.extend({
        animationDuration: 0,
        itemView: CapabilityGroupView,
        listen: {
            visibilityChange: 'onVisibilityChange'
        },

        onVisibilityChange: function() {
            this.$el.toggleClass('role-capabilities_single-group', this.visibleItems.length === 1);
        }
    });

    return RoleCapabilitiesView;
});
