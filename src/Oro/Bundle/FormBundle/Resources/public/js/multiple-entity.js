define(function(require) {
    'use strict';

    const _ = require('underscore');
    const routing = require('routing');
    const Backbone = require('backbone');
    const EntityView = require('./multiple-entity/view');
    const DialogWidget = require('oro/dialog-widget');
    const $ = Backbone.$;

    /**
     * @export  oroform/js/multiple-entity
     * @class   oroform.MultipleEntity
     * @extends Backbone.View
     */
    const MultipleEntityView = Backbone.View.extend({
        template: require('tpl-loader!oroform/js/multiple-entity/templates/multiple-entities.html'),
        elementTemplate: require('tpl-loader!oroform/js/multiple-entity/templates/multiple-entity.html'),

        options: {
            addedElement: null,
            allowAction: true,
            collection: null,
            defaultElement: null,
            elementTemplate: null,
            entitiesContainerSelector: '.entities',
            itemsPerRow: 4,
            name: null,
            removedElement: null,
            selectionUrl: null,
            selectionUrlMethod: null,
            selectionRouteName: null,
            selectionRouteParams: {},
            selectorWindowTitle: null,
            template: null
        },

        events: {
            'click .add-btn': 'addEntities'
        },

        /**
         * @inheritdoc
         */
        constructor: function MultipleEntityView(options) {
            MultipleEntityView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            if (typeof this.options.template === 'string') {
                this.template = _.template(this.options.template);
            }
            if (typeof this.options.elementTemplate === 'string') {
                this.elementTemplate = _.template(this.options.elementTemplate);
            }
            this.listenTo(this.getCollection(), 'add', this.addEntity);
            this.listenTo(this.getCollection(), 'reset', this._onCollectionReset);
            this.listenTo(this.getCollection(), 'remove', this.removeDefault);

            this.$addedEl = $(this.options.addedElement);
            this.$removedEl = $(this.options.removedElement);
            if (this.options.defaultElement) {
                this.listenTo(this.getCollection(), 'defaultChange', this.updateDefault);
                this.$defaultEl = this.$el.closest('form').find('[name$="[' + this.options.defaultElement + ']"]');
            } else {
                this.$defaultEl = $();
            }

            this.initialCollectionItems = [];
            this.addedCollectionItems = [];
            this.removedCollectionItems = [];

            this.render();
        },

        handleRemove: function(item) {
            const itemId = item && item.get('id');
            if (!itemId) {
                return;
            }

            const addedElVal = this.$addedEl.val();
            const removedElVal = this.$removedEl.val();

            let added = (addedElVal && addedElVal.split(',')) || [];
            const removed = (removedElVal && removedElVal.split(',')) || [];

            if (_.contains(added, itemId)) {
                added = _.without(added, itemId);
            }
            if (!_.contains(removed, itemId)) {
                removed.push(itemId);
            }

            this.addedCollectionItems = added;
            this.removedCollectionItems = removed;

            this.$addedEl.val(added.join(','));
            this.$removedEl.val(removed.join(','));
        },

        removeAll: function() {
            this.addedCollectionItems = [];
            this.$addedEl.val('');

            this.removedCollectionItems = _.clone(this.initialCollectionItems);
            this.$removedEl.val(this.removedCollectionItems.join(','));
            this.getCollection().reset([]);
        },

        removeDefault: function(item) {
            if (item.get('isDefault')) {
                this.$defaultEl.val('');
            }
        },

        updateDefault: function(item) {
            this.$defaultEl.val(item.get('id'));
        },

        getCollection: function() {
            return this.options.collection;
        },

        _onCollectionReset: function(items) {
            this._resortCollection();
            this.$entitiesContainer.empty();
            items.each(function(item) {
                this.addEntity(item);
            }, this);

            this.initialCollectionItems = this.getCollection().map(function(model) {
                return model.get('id');
            });
            this.addedCollectionItems = [];
            this.removedCollectionItems = [];
        },

        _isInitialCollectionItem: function(itemId) {
            const isInitial = !!_.find(this.initialCollectionItems, function(id) {
                return String(id) === String(itemId);
            });
            return isInitial;
        },

        _isAddedCollectionItem: function(itemId) {
            const isAdded = !!_.find(this.addedCollectionItems, function(id) {
                return String(id) === String(itemId);
            });
            return isAdded;
        },

        _isRemovedCollectionItem: function(itemId) {
            const isRemoved = !!_.find(this.removedCollectionItems, function(id) {
                return String(id) === String(itemId);
            });
            return isRemoved;
        },

        _resortCollection: function() {
            this.getCollection().comparator = function(model) {
                if (model.get('isDefault')) {
                    return 'A';
                } else {
                    return model.get('label');
                }
            };
            this.getCollection().sort();
        },

        addEntity: function(item) {
            if (item.get('id') === this.$defaultEl.val()) {
                item.set('isDefault', true);
            }
            const entityView = new EntityView({
                model: item,
                name: this.options.name,
                hasDefault: this.options.defaultElement,
                template: this.elementTemplate
            });
            entityView.on('removal', this.handleRemove.bind(this));
            this.$entitiesContainer.append(entityView.render().$el);
        },

        addEntities: function(e) {
            if (!this.selectorDialog) {
                let url = this._getSelectionWidgetUrl();
                const routeAdditionalParams = $(e.target).data('route_additional_params');
                if (routeAdditionalParams) {
                    url = url + (url.indexOf('?') === -1 ? '?' : '&') + $.param(routeAdditionalParams);
                }

                this.selectorDialog = new DialogWidget({
                    url: url,
                    method: this.options.selectionUrlMethod || 'GET',
                    title: this.options.selectorWindowTitle,
                    stateEnabled: false,
                    dialogOptions: {
                        modal: true,
                        width: 1024,
                        height: 500,
                        close: () => {
                            this.selectorDialog = null;
                        }
                    }
                });
                this.selectorDialog.on('completeSelection', this.processSelectedEntities.bind(this));
                this.selectorDialog.render();
            }
        },

        _getSelectionWidgetUrl: function() {
            const url = this.options.selectionUrl ||
                routing.generate(this.options.selectionRouteName, this.options.selectionRouteParams);
            const separator = url.indexOf('?') > -1 ? '&' : '?';
            const added = this.$addedEl.val();
            const removed = this.$removedEl.val();
            const defaultEl = this.$defaultEl.val();

            return url + separator +
                'added=' + (added || '') +
                '&removed=' + (removed || '') +
                '&default=' + (defaultEl || '');
        },

        _initWidgets: function() {
            _.delay(() => {
                this.$el.inputWidget('seekAndCreate');
            });
        },

        processSelectedEntities: function(added, addedModels, removed) {
            const self = this;

            _.intersection(added, removed).forEach(function(itemId) {
                if (self._isInitialCollectionItem(itemId)) {
                    added = _.without(added, itemId);
                    removed = _.without(removed, itemId);
                    return;
                }

                if (self._isAddedCollectionItem(itemId)) {
                    added = _.without(added, itemId);
                    return;
                }

                if (self._isRemovedCollectionItem(itemId)) {
                    removed = _.without(removed, itemId);
                }
            });

            this.addedCollectionItems = added;
            this.removedCollectionItems = removed;

            this.$addedEl.val(added.join(','));
            this.$removedEl.val(removed.join(','));

            _.each(addedModels, model => {
                this.getCollection().add(model);
            });
            for (let i = 0; i < removed.length; i++) {
                const model = this.getCollection().get(removed[i]);
                if (model) {
                    model.set('id', null);
                    model.destroy();
                }
            }

            this.selectorDialog.remove();

            this._initWidgets();
        },

        render: function() {
            this.$el.html(this.template());

            if (!this.options.allowAction) {
                this.$el.children('.actions.clearfix').remove();
            }

            this.$entitiesContainer = this.$el.find(this.options.entitiesContainerSelector);

            this._initWidgets();
            return this;
        }
    });

    return MultipleEntityView;
});
