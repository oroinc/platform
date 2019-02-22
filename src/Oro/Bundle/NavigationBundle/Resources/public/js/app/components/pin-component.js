define(function(require) {
    'use strict';

    var PinComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var PageStateView = require('oronavigation/js/app/views/page-state-view');
    var ButtonView = require('oronavigation/js/app/views/bookmark-button-view');
    var PinBarView = require('oronavigation/js/app/views/pin-bar-view');
    var DropdownView = require('oronavigation/js/app/views/pin-dropdown-view');
    var ItemView = require('oronavigation/js/app/views/pin-item-view');
    var BaseBookmarkComponent = require('oronavigation/js/app/components/base/bookmark-component');
    var PinbarCollection = require('oronavigation/js/app/models/pinbar-collection');

    PinComponent = BaseBookmarkComponent.extend({
        typeName: 'pinbar',

        listen: {
            'page:request mediator': 'refreshPinbar'
        },

        collectionModel: PinbarCollection,

        /**
         * @inheritDoc
         */
        constructor: function PinComponent() {
            PinComponent.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            PinComponent.__super__.initialize.call(this, options);

            this.refreshPinbar();
        },

        _createSubViews: function() {
            this._createButtonView();
            this._createBarView();
            this._createDropdownView();
            this._createPageStateView();
        },

        /**
         * Create view for pin button
         *
         * @protected
         */
        _createButtonView: function() {
            var options = this._options.buttonOptions || {};
            var collection = this.collection;

            _.extend(options, {
                el: this._options._sourceElement,
                autoRender: true,
                collection: collection
            });

            this.button = new ButtonView(options);
        },

        /**
         * Create view for pin bar
         *
         * @protected
         */
        _createBarView: function() {
            var options = this._options.barOptions || {};
            var collection = this.collection;

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: ItemView
            });

            this.pinBar = new PinBarView(options);
        },

        /**
         * Create view for pins in dropdown
         *
         * @protected
         */
        _createDropdownView: function() {
            var options = this._options.dropdownOptions || {};
            var collection = this.collection;
            var pinBar = this.pinBar;

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: ItemView,
                filterer: function(item) {
                    return !pinBar.isVisibleItem(item);
                },
                position: function() {
                    if (pinBar.el) {
                        var left = Math.ceil(pinBar.$el.position().left);

                        return {
                            left: _.isRTL() ? left : left + Math.ceil(pinBar.$el.width())
                        };
                    } else {
                        return null;
                    }
                }
            });

            this.dropdown = new DropdownView(options);
        },

        _createPageStateView: function() {
            var options = this._options.pageStateOptions || {};

            _.extend(options, {
                collection: this.collection
            });

            this.pageState = new PageStateView(options);

            mediator.setHandler('isPageStateChanged', this.pageState.isStateChanged.bind(this.pageState));
        },

        actualizeAttributes: function(model) {
            model.set('type', this.typeName);
            model.set('position', 0);
        },

        /**
         * @inheritDoc
         */
        toRemove: function(model) {
            var self = this;

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
                },
                complete: function() {
                    if (mediator.execute('compareUrl', model.get('url'))) {
                        // remove 'restore' param from URL, if pin was removed for current page
                        mediator.execute('changeUrlParam', 'restore', null);
                    }

                    self.refreshPinbar();
                }
            });
        },

        /**
         * @inheritDoc
         */
        toAdd: function(model) {
            var self = this;
            this.actualizeAttributes(model);
            model.save(null, {
                success: function() {
                    if (model.get('url') !== mediator.execute('currentUrl')) {
                        // if URL was changed on server, applies this changes for current page
                        mediator.execute('changeUrl', model.get('url'), {replace: true});
                    }
                },
                errorHandlerMessage: function(event, xhr) {
                    var item;

                    if (xhr.status === 422) {
                        item = self.collection.find(function(item) {
                            return item.get('url') === model.get('url');
                        });

                        // Makes error show if a validation error occurs, but item with matching URL not found.
                        if (item) {
                            return false;
                        }
                    }

                    return true;
                },
                error: function(data, xhr) {
                    if (xhr.status === 422) {
                        // Suppress error if it's 422 response
                        model.unset('id').destroy();
                    }
                },
                complete: function() {
                    self.refreshPinbar({
                        complete: function() {
                            var newModel = self.collection.find(function(item) {
                                return item.get('url') === model.get('url');
                            });

                            // Triggers "add" event on pinbars collection if the newly added pin was found.
                            if (newModel) {
                                self.collection.trigger('add', newModel, self.collection, {});
                            }
                        }
                    });
                }
            });
        },

        /**
         * @param {Object=} options
         * @returns {jqXHR}
         */
        refreshPinbar: function(options) {
            options = _.extend({url: routing.generate(this.route, {type: this.typeName}), reset: true}, options || {});

            return this.collection.fetch(options);
        }
    });

    return PinComponent;
});
