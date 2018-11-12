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

        /**
         * @inheritDoc
         */
        constructor: function CapabilityItemView() {
            CapabilityItemView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            CapabilityItemView.__super__.render.call(this);

            var $input = this.$('input');

            $input.inputWidget('isInitialized')
                ? $input.inputWidget('refresh')
                : $input.inputWidget('create');
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = CapabilityItemView.__super__.getTemplateData.call(this);
            data.isAccessLevelChanged = this.model.isAccessLevelChanged();
            return data;
        },

        /**
         * @param e
         */
        onChange: function(e) {
            var value = this.model.get(e.currentTarget.checked ? 'selected_access_level' : 'unselected_access_level');
            this.model.set('access_level', value);
        }
    });

    return CapabilityItemView;
});
