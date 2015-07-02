define(function (require) {
    'use strict';

    var StatefulModel,
        BaseModel = require('./model');

    StatefulModel = BaseModel.extend({
        getState: function () {},
        setState: function (state) {
            if (state instanceof Backbone.Model === false) {
                throw new Error('State object should be instance of Backbone.Model');
            }
        }
    });

    return StatefulModel;
});

