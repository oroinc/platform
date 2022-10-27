define(function(require) {
    'use strict';

    const _ = require('underscore');

    /**
     * Implements access over getter properties and allows to iterate entity over its fields
     * Provides series of helper methods
     *
     * @param {Object} properties definition of enumerable getters for entity fields
     * @param {Object} [properties.__entity] definition of getter method for entity's information
     * @param {Object} [properties.__field] definition of getter method for field's information
     * @constructor
     */
    function EntityTreeNode(properties) {
        Object.defineProperties(this, properties);
    }

    Object.defineProperties(EntityTreeNode.prototype, {
        __isField: {
            get: function() {
                return '__field' in this;
            }
        },
        __isEntity: {
            get: function() {
                return '__entity' in this;
            }
        },
        __hasScalarFieldsInSubtree: {
            value: function(deepsLimit) {
                if (deepsLimit === 0 || !this.__isEntity || this.__entity.fields.length === 0) {
                    return false;
                }
                let scalarField = _.findWhere(this, {__isEntity: false});
                if (!scalarField) {
                    scalarField = _.find(this, function(node) {
                        return node.__hasScalarFieldsInSubtree(deepsLimit - 1);
                    }, this);
                }
                return Boolean(scalarField);
            }
        }
    });

    return EntityTreeNode;
});
