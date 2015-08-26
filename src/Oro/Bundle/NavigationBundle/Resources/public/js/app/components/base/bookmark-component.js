define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/app/components/base/component',
    'oroui/js/error'
], function($, _, mediator, BaseComponent, error) {
    'use strict';

    var BaseBookmarkComponent;

    BaseBookmarkComponent = BaseComponent.extend({
        /**
         * Keeps separately extended options,
         * to prevent disposing the view each time by Composer
         */
        _options: {},

        typeName: null,

        listen: {
            'toAdd collection': 'toAdd',
            'toRemove collection': 'toRemove'
        },

        initialize: function(options) {
            var $dataEl = $(options.dataSource);
            var data = $dataEl.data('data');
            var extraOptions = $dataEl.data('options');
            $dataEl.remove();

            // create own property _options (not spoil prototype)
            this._options = _.defaults({}, options || {}, extraOptions);

            BaseBookmarkComponent.__super__.initialize.call(this, options);

            var $button = $(this._options.buttonOptions.el);
            var route = $button.data('navigation-items-route');
            if (!_.isEmpty(route)) {
                this.collection.model.prototype.route = route;
            }

            var typeName = $button.data('type-name');
            if (!_.isEmpty(typeName)) {
                this.typeName = typeName;
            }

            this.collection.reset(data);
            this._createSubViews();
        },

        _createSubViews: function() {
            // should be implemented in descendants
        },

        toRemove: function(model) {
            model.destroy({
                wait: true,
                error: function(model, xhr) {
                    if (xhr.status === 404 && !mediator.execute('retrieveOption', 'debug')) {
                        // Suppress error if it's 404 response and not debug mode
                        model.unset('id').destroy();
                    } else {
                        error.handle({}, xhr, {enforce: true});
                    }
                }
            });
        },

        toAdd: function(model) {
            var collection;
            collection = this.collection;
            this.actualizeAttributes(model);
            model.save(null, {
                success: function() {
                    var item;
                    item = collection.find(function(item) {
                        return item.get('url') === model.get('url');
                    });
                    if (item) {
                        model.destroy();
                    } else {
                        collection.unshift(model);
                    }
                }
            });
        },

        actualizeAttributes: function(model) {
            // should be implemented in descendants
        }
    });

    return BaseBookmarkComponent;
});
