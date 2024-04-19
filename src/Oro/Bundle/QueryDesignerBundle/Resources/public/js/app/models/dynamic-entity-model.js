import BaseModel from 'oroui/js/app/models/base/model';

const DynamicEntityModel = BaseModel.extend({
    defaults: {
        name: '',
        label: '',
        func: null
    },

    constructor: function DynamicEntityModel(...args) {
        DynamicEntityModel.__super__.constructor.apply(this, args);
    },

    getSelect2Data() {
        return {
            id: this.getSelect2Id(),
            name: this.get('name'),
            text: this.collection.resolveFunctionNameByData({
                label: this.get('label'),
                funcName: this.get('func').name
            })
        };
    },

    getSelect2Id() {
        return `${this.get('name')}_${this.get('id')}`;
    }
});

export default DynamicEntityModel;
