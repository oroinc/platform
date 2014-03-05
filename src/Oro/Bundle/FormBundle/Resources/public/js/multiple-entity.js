/* jshint devel:true */
/*global define*/
define(['underscore', 'backbone', './multiple-entity/view', './multiple-entity/model', 'oro/dialog-widget'
    ], function (_, Backbone, EntityView, MultipleEntityModel, DialogWidget) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroform/js/multiple-entity
     * @class   oroform.MultipleEntity
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            template: null,
            elementTemplate: null,
            entitiesContainerSelector: '.entities',
            name: null,
            collection: null,
            selectionUrl: null,
            addedElement: null,
            removedElement: null,
            defaultElement: null,
            itemsPerRow: 4,
            selectorWindowTitle: null
        },

        events: {
            'click .add-btn': 'addEntities'
        },

        initialize: function() {
            this.template = _.template(this.options.template)
            this.listenTo(this.getCollection(), 'add', this.addEntity);
            this.listenTo(this.getCollection(), 'reset', this._onCollectionReset);
            this.listenTo(this.getCollection(), 'remove', this.removeDefault);

            this.$addedEl = $(this.options.addedElement);
            this.$removedEl = $(this.options.removedElement);
            if (this.options.defaultElement) {
                this.listenTo(this.getCollection(), 'defaultChange', this.updateDefault);
                this.$defaultEl = this.$el.closest('form').find('[name$="[' + this.options.defaultElement + ']"]');
            }
            this.initialCollectionItems = [];
            this.addedCollectionItems = [];
            this.removedCollectionItems = [];

            this.render();
        },

        handleRemove: function (item) {
            var itemId = item && item.get('id');
            if (!itemId) {
                return;
            }

            var addedElVal = this.$addedEl.val();
            var removedElVal = this.$removedEl.val();

            var added = (addedElVal && addedElVal.split(',')) || [];
            var removed = (removedElVal && removedElVal.split(',')) || [];

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

        _onCollectionReset: function (items) {
            this._resortCollection();
            this.$entitiesContainer.empty();
            items.each(function(item) {
                this.addEntity(item);
            }, this);

            this.initialCollectionItems = this.getCollection().map(function (model) {
                return model.get('id');
            });
            this.addedCollectionItems = [];
            this.removedCollectionItems = [];
        },

        _isInitialCollectionItem: function (itemId) {
            var isInitial = !!_.find(this.initialCollectionItems, function (id) {
                return String(id) === String(itemId);
            });
            return isInitial;
        },

        _isAddedCollectionItem: function (itemId) {
            var isAdded = !!_.find(this.addedCollectionItems, function (id) {
                return String(id) === String(itemId);
            });
            return isAdded;
        },

        _isRemovedCollectionItem: function (itemId) {
            var isRemoved = !!_.find(this.removedCollectionItems, function (id) {
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
            if (item.get('id') == this.$defaultEl.val()) {
                item.set('isDefault', true);
            }
            var entityView = new EntityView({
                model: item,
                name: this.options.name,
                hasDefault: this.options.defaultElement,
                template: this.options.elementTemplate
            });
            entityView.on('removal', _.bind(this.handleRemove, this));
            this.$entitiesContainer.append(entityView.render().$el);
        },

        addEntities: function() {
            if (!this.selectorDialog) {
                var url = this.options.selectionUrl;
                var separator = url.indexOf('?') > -1 ? '&' : '?';
                this.selectorDialog = new DialogWidget({
                    url: url + separator
                        + 'added=' + this.$addedEl.val()
                        + '&removed=' + this.$removedEl.val()
                        + '&default=' + this.$defaultEl.val(),
                    title: this.options.selectorWindowTitle,
                    stateEnabled: false,
                    dialogOptions: {
                        'modal': true,
                        'width': 1024,
                        'height': 500,
                        'close': _.bind(function() {
                            this.selectorDialog = null;
                        }, this)
                    }
                });
                this.selectorDialog.on('completeSelection', _.bind(this.processSelectedEntities, this));
                this.selectorDialog.render();
            }
        },

        processSelectedEntities: function (added, addedModels, removed) {
            var self = this;

            _.intersection(added, removed).forEach(function (itemId) {
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

            _.each(addedModels, _.bind(function(model) {
                this.getCollection().add(model);
            }, this));
            for (var i = 0; i < removed.length; i++) {
                var model = this.getCollection().get(removed[i]);
                if (model) {
                    model.set('id', null);
                    model.destroy()
                }
            }

            this.selectorDialog.remove();
        },

        render: function() {
            this.$el.html(this.template());
            this.$entitiesContainer = this.$el.find(this.options.entitiesContainerSelector);

            return this;
        }
    });
});
