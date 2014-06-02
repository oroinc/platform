/*global define*/
define(['underscore', 'backbone', 'orotranslation/js/translator',
    'oroui/js/mediator', 'oroui/js/messenger', 'oro/dialog-widget',
    'oronote/js/note/view', 'oronote/js/note/collection'
], function (
    _, Backbone, __,
    mediator, messenger, DialogWidget,
    NoteView, NoteCollection
) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oronote/js/note-list
     * @class   oronote.NoteList
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            'template': null,
            'listUrl': null,
            'createItemUrl': null,
            'updateItemUrl': null,
            'labels': {
                noData: '',
                addDialogTitle: '',
                editDialogTitle: '',
                itemSaved: ''
            }
        },

        initialize: function () {
            this.options.collection = this.options.collection || new NoteCollection();
            this.options.collection.url = this._getUrl('listUrl');

            this.listenTo(this.getCollection(), 'add', this._onItemAdded);
            this.listenTo(this.getCollection(), 'reset', this._onItemsAdded);

            this.$itemsContainer  = $('<div class="items"/>');
            if (!this.$el.find('.items').length) {
                this.$el.append(this.$itemsContainer);
            }

            this.$noDataContainer = $('<div class="no-data"><span>' + this._getLabel('noData') + '</span></div>');
            if (!this.$el.find('.no-data').length) {
                this.$el.append(this.$noDataContainer);
            }
        },

        getCollection: function () {
            return this.options.collection;
        },

        reloadItems: function () {
            this.getCollection().fetch({reset: true});
        },

        addItem: function () {
            this._openItemEditForm(this._getUrl('addDialogTitle'), this._getUrl('createItemUrl'));
        },

        editItem: function (itemView, item) {
            this._openItemEditForm(this._getUrl('editDialogTitle'), this._getUrl('updateItemUrl', item));
        },

        _onItemsAdded: function (items) {
            this.$itemsContainer.empty();
            if(items.length > 0){
                this._hideEmptyMessage();
                items.each(function (item) {
                    this._onItemAdded(item);
                }, this);
            } else {
                this._showEmptyMessage();
            }
        },

        _onItemAdded: function (item) {
            if (!this.$el.find('#' + this._buildItemIdAttribute(item.id)).length) {
                var itemView = new NoteView({
                    template: '#template-note-item',
                    buildItemIdAttribute: this._buildItemIdAttribute,
                    model: item
                });
                itemView.on('edit', _.bind(this.editItem, this));
                this.$itemsContainer.append(itemView.render().$el);
            }
        },

        _getUrl: function (optionsKey) {
            if (_.isFunction(this.options[optionsKey])) {
                return this.options[optionsKey].apply(this, Array.prototype.slice.call(arguments, 1));
            }
            return this.options[optionsKey];
        },

        _getLabel: function (labelKey) {
            return this.options['labels'][labelKey];
        },

        _buildItemIdAttribute: function (id) {
            return 'note-' + id;
        },

        _hideEmptyMessage: function() {
            this.$noDataContainer.hide();
            this.$itemsContainer.show();
        },

        _showEmptyMessage: function () {
            this.$noDataContainer.show();
            this.$itemsContainer.hide();
        },

        _openItemEditForm: function (title, url) {
            if (!this.itemEditDialog) {
                this.itemEditDialog = new DialogWidget({
                    'url': url,
                    'title': title,
                    'regionEnabled': false,
                    'incrementalPosition': false,
                    'dialogOptions': {
                        'modal': true,
                        'resizable': false,
                        'width': 475,
                        'autoResize': true,
                        'close': _.bind(function () {
                            delete this.itemEditDialog;
                        }, this)
                    }
                });
                this.itemEditDialog.render();
                mediator.on(
                    "hash_navigation_request:start",
                    _.bind(function () {
                        if (this.itemEditDialog) {
                            this.itemEditDialog.remove();
                        }
                    }, this)
                );
                this.itemEditDialog.on('formSave', _.bind(function () {
                    this.itemEditDialog.remove();
                    messenger.notificationFlashMessage('success', this._getLabel('itemSaved'));
                    this.reloadItems();
                }, this));
            }
        }
    });
});
