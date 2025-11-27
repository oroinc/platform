import BaseModel from 'oroui/js/app/models/base/model';

const HistoryStateModel = BaseModel.extend({
    /**
     * @inheritdoc
     */
    constructor: function HistoryStateModel(attrs, options) {
        HistoryStateModel.__super__.constructor.call(this, attrs, options);
    },

    defaults: function() {
        return {
            data: {}
        };
    }
});

export default HistoryStateModel;
