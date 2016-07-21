define(function(require) {
    'use strict';

    var CapabilityItemView;
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orouser/js/views/role-view
     */
    CapabilityItemView = BaseView.extend({
        className: 'role-capability__item',
        template: require('tpl!orouser/templates/role/capability-item.html'),
        autoRender: true,
        listen: {
            'change:access_level model': 'render'
        },
        events: {
            'change [type=checkbox]': 'onChange'
        },

        getTemplateData: function() {
            var data = CapabilityItemView.__super__.getTemplateData.call(this);
            data.isAccessLevelChanged = this.model.isAccessLevelChanged();
            return data;
        },

        onChange: function(e) {
            var value = this.model.get(e.currentTarget.checked ? 'selected_access_level' : 'unselected_access_level');
            this.model.set('access_level', value);
        }
    });

    return CapabilityItemView;
});
