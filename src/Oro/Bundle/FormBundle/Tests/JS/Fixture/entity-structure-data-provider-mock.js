define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var nodeProperties = {
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
        var properties = nodeProperties;

        if ('__field' in node) {
            var fieldData = _.clone(node.__field);
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
        this._entitiesData = $.extend(true, {}, entitiesData);
        _prepareNode(this._entitiesData);
    }

    EntityStructureDataProviderMock.prototype = {
        getEntityTreeNodeByPropertyPath: function(propertyPath) {
            var parts = propertyPath.split('.');
            var node = this._entitiesData[parts[0]];
            for (var i = 1; node && i < parts.length; i++) {
                node = node[parts[i]];
            }
            return node;
        }
    };

    return EntityStructureDataProviderMock;
});
