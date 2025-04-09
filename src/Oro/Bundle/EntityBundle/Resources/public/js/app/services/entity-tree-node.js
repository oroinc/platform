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
        },
        __fieldProps: {
            value(propsName = []) {
                return propsName.length ? propsName.reduce((field, propName) => {
                    if (this.__field[propName]) {
                        field.push(this.__field[propName]);
                    }
                    return field;
                }, []) : this.__field;
            }
        },
        __children: {
            get() {
                return Object.fromEntries(Object.entries(this).filter(([name]) => {
                    return !name.startsWith('__');
                }));
            }
        },
        __hasChildren: {
            get() {
                return !!Object.keys(this).length;
            }
        }
    });

    return EntityTreeNode;
});
