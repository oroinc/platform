import _ from 'underscore';
import Chaplin from 'chaplin';
import BaseModel from './model';

/**
 * @class BaseCollection
 * @extends Chaplin.Collection
 */
const BaseCollection = Chaplin.Collection.extend(/** @lends BaseCollection.prototype */{
    cidPrefix: 'cl',

    model: BaseModel,

    constructor: function BaseCollection(data, options) {
        this.cid = _.uniqueId(this.cidPrefix);
        BaseCollection.__super__.constructor.call(this, data, options);
    },

    /**
     * Returns additional parameters to be merged into serialized object
     */
    serializeExtraData: function() {
        return {};
    },

    modelId(attrs) {
        return attrs[this.model.prototype.idAttribute || 'id'];
    }
});

export default BaseCollection;
