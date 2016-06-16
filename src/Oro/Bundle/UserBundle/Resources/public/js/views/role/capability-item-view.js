define(function(require) {
    'use strict';

    var CapabilityItemView;
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orouser/js/views/role-view
     */
    CapabilityItemView = BaseView.extend({
        className: 'role-capability__item',
        template: require('tpl!orouser/templates/role/capability-item.html'),
        autoRender: true,
        listen: {
            'change:access_level model': 'onAccessLevelChange'
        },
        events: {
            'change [type=checkbox]': 'onChange'
        },

        onAccessLevelChange: function(model) {
            mediator.trigger('securityAccessLevelsComponent:link:click', {
                accessLevel: model.get('access_level'),
                identityId: model.get('identity'),
                permissionName: model.get('name')
            });
            this.render();
        },

        onChange: function(e) {
            var value = this.model.get(e.currentTarget.checked ? 'selected_access_level' : 'unselected_access_level');
            this.model.set('access_level', value);
        }
    });

    return CapabilityItemView;
});
