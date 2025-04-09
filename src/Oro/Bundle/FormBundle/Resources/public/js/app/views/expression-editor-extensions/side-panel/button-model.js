import BaseModel from 'oroui/js/app/models/base/model';

const SidePanelButtonModel = BaseModel.extend({
    /**
     * @inheritdoc
     */
    defaults: {
        name: '',
        label: '',
        order: 0,
        enabled: true,
        handler: null,
        title: '',
        className: 'cm-btn',
        extraClassName: '',
        operation: 'default',
        viewOptions: null,
        type: 'button'
    },

    /**
     * @inheritdoc
     */
    constructor: function SidePanelButtonModel(attrs, options) {
        SidePanelButtonModel.__super__.constructor.call(this, attrs, options);
    },

    isAllowed(allowedOperations) {
        if (this.get('operation') === 'default') {
            return true;
        }

        if (Array.isArray(this.get('operation'))) {
            return this.get('operation').some(item => allowedOperations.includes(item));
        }

        return allowedOperations.includes(this.get('operation'));
    }
});

export default SidePanelButtonModel;
