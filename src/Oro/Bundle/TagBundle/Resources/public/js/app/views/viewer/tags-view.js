define(function(require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view');
    var BaseModel = require('oroui/js/app/models/base/model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * Tags view, able to handle either `collection` of tags or plain array of `items`.
     *
     * @class
     */
    var TagsView = BaseView.extend({
        template: require('tpl!orotag/templates/viewer/tags-view.html'),
        events: {
            'change model': 'render'
        },
        initialize: function(options) {
            if (!options.collection) {
                if (options.items) {
                    this.collection = new BaseCollection(options.items, {
                        model: BaseModel,
                        comparator: function(item) {
                            return !item.get('owner');
                        }
                    });
                } else {
                    throw new Error('You mist specify either `collection` or `items` option');
                }
            }
            return TagsView.__super__.initialize.apply(this, arguments);
        }
    });

    return TagsView;
});
