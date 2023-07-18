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
        className: 'cm-btn'
    },

    /**
     * @inheritdoc
     */
    constructor: function SidePanelButtonModel(attrs, options) {
        SidePanelButtonModel.__super__.constructor.call(this, attrs, options);
    }
});

export default SidePanelButtonModel;
