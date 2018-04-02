define(function(require) {
    'use strict';

    var BaseModel;
    var Chaplin = require('chaplin');

    /**
     * @class BaseModel
     * @extends Chaplin.Model
     */
    BaseModel = Chaplin.Model.extend(/** @lends BaseModel.prototype */{
        constructor: function BaseModel(data, options) {
            BaseModel.__super__.constructor.call(this, data, options);
        }
    });

    return BaseModel;
});
