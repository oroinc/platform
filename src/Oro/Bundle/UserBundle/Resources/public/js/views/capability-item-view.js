define(function(require) {
    'use strict';

    var CapabilityItemView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orouser/js/views/role-view
     */
    CapabilityItemView = BaseView.extend({
        className: 'role-capability__item',
        template: require('tpl!orouser/templates/capability-item.html'),
        autoRender: true,
        listen: {
            'change:accessLevel model': 'onAccessLevelChange'
        },
        events: {
            'change [type=checkbox]': 'onChange'
        },
        onAccessLevelChange: function(model) {
            var value = _.pick(model.toJSON(), 'accessLevel', 'identityId', 'permissionName');
            mediator.trigger('securityAccessLevelsComponent:link:click', value);
            this.render();
        },
        onChange: function(e) {
            var value = this.model.get(e.currentTarget.checked ? 'selected_value' : 'unselected_value');
            this.model.set('accessLevel', value);
        }
    });

    return CapabilityItemView;
});
