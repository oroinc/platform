define(function(require) {
    'use strict';

    var CapabilityItemView;
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orouser/js/views/role-view
     */
    CapabilityItemView = BaseView.extend({
        className: 'role-capability__item',
        template: require('tpl!orouser/templates/capability-item.html'),
        autoRender: true,
        listen: {
            'change:value model': 'render'
        },
        events: {
            'change [type=checkbox]': 'onChange'
        },
        onChange: function(e) {
            var value = this.model.get(e.currentTarget.checked ? 'selected_value' : 'unselected_value');
            this.model.set('value', value);
        }
    });

    return CapabilityItemView;
});
