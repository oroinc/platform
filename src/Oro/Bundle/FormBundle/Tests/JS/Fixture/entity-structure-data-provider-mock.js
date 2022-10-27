define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const nodeProperties = {
        __isField: {
            get: function() {
                return '__field' in this;
            }
        },
        __isEntity: {
            get: function() {
                return _.keys(_.omit(this, '__field')).length > 0;
            }
        },
        __hasScalarFieldsInSubtree: {
            value: function() {
                return true;
            }
        }
    };

    function _prepareNode(node) {
        let properties = nodeProperties;

        if ('__field' in node) {
            const fieldData = _.clone(node.__field);
            properties = _.extend({}, properties, {
                __field: {
                    get: function() {
                        return fieldData;
                    }
                }
            });
            delete node.__field;
        }

        Object.defineProperties(node, properties);
        _.each(node, _prepareNode);
    }

    function EntityStructureDataProviderMock(entitiesData) {
        this.entityTree = $.extend(true, {}, entitiesData);
        _prepareNode(this.entityTree);
    }

    EntityStructureDataProviderMock.prototype = {
        getEntityTreeNodeByPropertyPath: function(propertyPath) {
            const parts = propertyPath.split('.');
            let node = this.entityTree[parts[0]];
            for (let i = 1; node && i < parts.length; i++) {
                node = node[parts[i]];
            }
            return node;
        }
    };

    return EntityStructureDataProviderMock;
});
