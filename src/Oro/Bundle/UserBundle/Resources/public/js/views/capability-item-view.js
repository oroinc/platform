define(function(require) {
    'use strict';

    var CapabilityItemView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orouser/js/views/role-view
     */
    CapabilityItemView = BaseView.extend({
        className: 'security-row row-fluid',
        template: require('tpl!orouser/templates/capability-item.html'),
        autoRender: true,
        listen: {
            'change:accessLevel model': 'render'
        },
        events: {
            'change [type=checkbox]': 'onChange'
        },
        onChange: function(e) {
            this.model.set('accessLevel', e.currentTarget.checked ? 5 : 0);
        }
    });

    return CapabilityItemView;
});
