define(function (require) {
    'use strict';

    var StatefulModel,
        _ = require('underscore'),
        Backbone = require('backbone'),
        BaseModel = require('./model');

    StatefulModel = BaseModel.extend({
        initialize: function () {
            var that = this;
            StatefulModel.__super__.initialize.apply(this, arguments);
            if ('observedAttributes' in that) {
                _.each(that.observedAttributes, function (attrName){
                    var prop = that.get(attrName);
                    if (prop) {
                        if (prop instanceof Backbone.Collection) {
                            prop.on('change add remove', function () {
                                that.trigger('stateChange');
                            });
                        } else {
                            that.on('change:' + attrName, function () {
                                that.trigger('stateChange');
                            })
                        }
                    }
                });
            } else {
                this.on('change', function () {
                    this.trigger('stateChange');
                })
            }
        },
        getState: function () {
            throw new Error('Method getState should be defined in a inherited class.');
        },
        setState: function () {
            throw new Error('Method getState should be defined in a inherited class.');
        }
    });

    return StatefulModel;
});

