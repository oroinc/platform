import Chaplin from 'chaplin';

/**
 * @class BaseModel
 * @extends Chaplin.Model
 */
const BaseModel = Chaplin.Model.extend(/** @lends BaseModel.prototype */{
    constructor: function BaseModel(data, options) {
        BaseModel.__super__.constructor.call(this, data, options);
    }
});

export default BaseModel;
