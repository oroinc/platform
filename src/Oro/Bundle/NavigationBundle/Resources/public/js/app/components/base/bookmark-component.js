define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseNavigationItemCollection = require('oronavigation/js/app/models/base/collection');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const BaseBookmarkComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function BaseBookmarkComponent(options) {
            BaseBookmarkComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const $dataEl = $(options.dataSource);
            const extraOptions = $dataEl.data('options');
            $dataEl.remove();

            this.collection = new this.collectionModel;

            // create own property _options (not spoil prototype)
            this._options = _.defaults(_.omit(options, '_subPromises'), extraOptions);

            BaseBookmarkComponent.__super__.initialize.call(this, options);

            this.route = options._sourceElement.data('navigation-items-route') || this.route;
            if (!_.isEmpty(this.route)) {
                this.collection.model = this.collection.model.extend({route: this.route});
            }

            const typeName = options._sourceElement.data('type-name');
            if (!_.isEmpty(typeName)) {
                this.typeName = typeName;
            }

            const data = $dataEl.data('data');
            if (data) {
                this.collection.reset(data);
            }

            // wait for controller to be ready before initializing views
            this.listenToOnce(mediator, 'page:update', this._createSubViews.bind(this));
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
            const collection = this.collection;
            this.actualizeAttributes(model);
            model.save(null, {
                success: function() {
                    const item = collection.find(function(item) {
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
