define(function(require) {
    'use strict';
    var BaseClass = require('oroui/js/base-class');
    var BaseModel = require('oroui/js/app/models/base/model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * Tags view, able to handle either `collection` of tags or plain array of `items`.
     *
     * @class
     */
    var TagsReader = BaseClass.extend({
        read: function(value) {
            return {
                collection: new BaseCollection(value, {
                    model: BaseModel,
                    comparator: function(item) {
                        return !item.get('owner');
                    }
                })
            };
        }
    });

    return TagsReader;
});
