import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orouser/templates/role/capability-item.html';

/**
 * @export orouser/js/views/role-view
 */
const CapabilityItemView = BaseView.extend({
    className: 'role-capability__item',

    template,

    autoRender: true,

    listen: {
        'change:access_level model': 'render'
    },

    events: {
        'change [type=checkbox]': 'onChange'
    },

    /**
     * @inheritdoc
     */
    constructor: function CapabilityItemView(options) {
        CapabilityItemView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    render: function() {
        CapabilityItemView.__super__.render.call(this);

        const $input = this.$('input');

        $input.inputWidget('isInitialized')
            ? $input.inputWidget('refresh')
            : $input.inputWidget('create');
    },

    /**
     * @inheritdoc
     */
    getTemplateData: function() {
        const data = CapabilityItemView.__super__.getTemplateData.call(this);
        data.isAccessLevelChanged = this.model.isAccessLevelChanged();
        return data;
    },

    /**
     * @param e
     */
    onChange: function(e) {
        const value = this.model.get(e.currentTarget.checked ? 'selected_access_level' : 'unselected_access_level');
        this.model.set('access_level', value);
    }
});

export default CapabilityItemView;
