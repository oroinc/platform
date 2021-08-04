define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const tools = require('oroui/js/tools');
    const ButtonView = require('oronavigation/js/app/views/bookmark-button-view');
    const PinBarView = require('oronavigation/js/app/views/pin-bar-view');
    const DropdownView = require('oronavigation/js/app/views/pin-dropdown-view');
    const ItemView = require('oronavigation/js/app/views/pin-item-view');
    const BaseBookmarkComponent = require('oronavigation/js/app/components/base/bookmark-component');
    const PinbarCollection = require('oronavigation/js/app/models/pinbar-collection');

    const PinComponent = BaseBookmarkComponent.extend({
        relatedSiblingComponents: {
            pageStateComponent: 'page-state-component'
        },

        typeName: 'pinbar',

        listen: {
            'page:request mediator': 'refreshPinbar',

            'add collection': 'togglePageStateTrace',
            'remove collection': 'togglePageStateTrace'
        },

        collectionModel: PinbarCollection,

        /**
         * @inheritdoc
         */
        constructor: function PinComponent(options) {
            PinComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            PinComponent.__super__.initialize.call(this, options);

            if (!this.pageStateComponent) {
                throw new Error('Instance of PageStateComponent is required for Pinned tabs');
            }

            this.pageStateComponent.view.setStateTraceRequiredChecker(this.isPageStateTraceRequired.bind(this));

            this.refreshPinbar();
        },

        _createSubViews: function() {
            this._createButtonView();
            this._createBarView();
            this._createDropdownView();
        },

        /**
         * Create view for pin button
         *
         * @protected
         */
        _createButtonView: function() {
            const options = this._options.buttonOptions || {};
            const collection = this.collection;

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
            const options = this._options.barOptions || {};
            const collection = this.collection;

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
            const options = this._options.dropdownOptions || {};
            const collection = this.collection;
            const pinBar = this.pinBar;

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: ItemView,
                filterer: function(item) {
                    return !pinBar.isVisibleItem(item);
                },
                position: function() {
                    if (pinBar.el) {
                        const left = Math.ceil(pinBar.$el.position().left);

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

        togglePageStateTrace: function() {
            this.pageStateComponent.view.toggleStateTrace();
        },

        isPageStateTraceRequired: function() {
            const urlObj = document.createElement('a');
            urlObj.href = mediator.execute('normalizeUrl', mediator.execute('currentUrl'));
            const queryObj = tools.unpackFromQueryString(urlObj.search);

            return this.collection.getCurrentModel() !== undefined && queryObj['restore'];
        },

        actualizeAttributes: function(model) {
            model.set('type', this.typeName);
            model.set('position', 0);
        },

        /**
         * @inheritdoc
         */
        toRemove: function(model) {
            const self = this;

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
         * @inheritdoc
         */
        toAdd: function(model) {
            const self = this;
            this.actualizeAttributes(model);
            model.save(null, {
                success: function() {
                    if (model.get('url') !== mediator.execute('currentUrl')) {
                        // if URL was changed on server, applies this changes for current page
                        mediator.execute('changeUrl', model.get('url'), {replace: true});
                    }
                },
                errorHandlerMessage: function(event, xhr) {
                    let item;

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
                            const newModel = self.collection.find(function(item) {
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
