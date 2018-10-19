define(function(require) {
    'use strict';

    var serializer;
    var _ = require('underscore');
    var Backbone = require('backbone');

    serializer = {
        serializeAttributes: function(model, attributes, modelStack) {
            if (modelStack === undefined) {
                modelStack = {};
            }
            modelStack[model.cid] = true;
            attributes = _.mapObject(attributes, function(value) {
                if (value instanceof Backbone.Model) {
                    value = this.serializeModelAttributes(value, model, modelStack);
                } else if (value instanceof Backbone.Collection) {
                    value = value.map(function(otherModel) {
                        return this.serializeModelAttributes(otherModel, model, modelStack);
                    }, this);
                }
                return value;
            }, this);
            delete modelStack[model.cid];
            return attributes;
        },

        serializeModelAttributes: function(model, currentModel, modelStack) {
            var attributes;
            if (model === currentModel || model.cid in modelStack) {
                return model.identifier || null;
            }
            attributes = _.isFunction(model.getAttributesAndRelationships)
                ? model.getAttributesAndRelationships()
                : _.isFunction(model.getAttributes) ? model.getAttributes() : model.attributes;
            return this.serializeAttributes(model, attributes, modelStack);
        }
    };

    return serializer;
});
