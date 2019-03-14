define(function(require) {
    'use strict';

    var BaseBookmarkComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseNavigationItemCollection = require('oronavigation/js/app/models/base/collection');
    var BaseComponent = require('oroui/js/app/components/base/component');

    BaseBookmarkComponent = BaseComponent.extend({
        /**
         * Keeps separately extended options,
         * to prevent disposing the view each time by Composer
         */
        _options: {},

        collectionModel: BaseNavigationItemCollection,

        typeName: null,

        route: 'oro_api_get_navigationitems',

        listen: {
            'toAdd collection': 'toAdd',
            'toRemove collection': 'toRemove'
        },

        /**
         * @inheritDoc
         */
        constructor: function BaseBookmarkComponent(options) {
            BaseBookmarkComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var $dataEl = $(options.dataSource);
            var extraOptions = $dataEl.data('options');
            $dataEl.remove();

            this.collection = new this.collectionModel;

            // create own property _options (not spoil prototype)
            this._options = _.defaults(_.omit(options, '_subPromises'), extraOptions);

            BaseBookmarkComponent.__super__.initialize.call(this, options);

            this.route = options._sourceElement.data('navigation-items-route') || this.route;
            if (!_.isEmpty(this.route)) {
                this.collection.model = this.collection.model.extend({route: this.route});
            }

            var typeName = options._sourceElement.data('type-name');
            if (!_.isEmpty(typeName)) {
                this.typeName = typeName;
            }

            var data = $dataEl.data('data');
            if (data) {
                this.collection.reset(data);
            }

            this._createSubViews();
        },

        _createSubViews: function() {
            // should be implemented in descendants
        },

        toRemove: function(model) {
            model.destroy({
                wait: true,
                errorHandlerMessage: function(event, xhr) {
                    // Suppress error if it's 404 response
                    return xhr.status !== 404;
                },
                error: function(model, xhr) {
                    if (xhr.status === 404 && !mediator.execute('retrieveOption', 'debug')) {
                        // Suppress error if it's 404 response and not debug mode
                        model.unset('id').destroy();
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
