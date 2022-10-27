define(function(require) {
    'use strict';

    const Chaplin = require('chaplin');

    /**
     * @class BaseModel
     * @extends Chaplin.Model
     */
    const BaseModel = Chaplin.Model.extend(/** @lends BaseModel.prototype */{
        constructor: function BaseModel(data, options) {
            BaseModel.__super__.constructor.call(this, data, options);
        }
    });

    return BaseModel;
});
