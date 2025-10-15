import BaseModel from 'oroui/js/app/models/base/model';

const FlowchartStateModel = BaseModel.extend({
    defaults: {
        transitionLabelsVisible: true
    },

    /**
     * @inheritdoc
     */
    constructor: function FlowchartStateModel(...args) {
        FlowchartStateModel.__super__.constructor.apply(this, args);
    }
});

export default FlowchartStateModel;
