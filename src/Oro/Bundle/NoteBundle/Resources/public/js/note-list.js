/*global define*/
define(['underscore', 'backbone', 'orotranslation/js/translator', 'oroui/js/messenger',
    'oroui/js/mediator', 'oroui/js/loading-mask', 'oro/dialog-widget', 'oroui/js/delete-confirmation',
    'oronote/js/note/view', 'oronote/js/note/model', 'oronote/js/note/collection', 'jquery-outer-html'],
function (
    _, Backbone, __, messenger,
    mediator, LoadingMask, DialogWidget, DeleteConfirmation,
    NoteView, NoteModel, NoteCollection
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
            template: null,
            itemTemplate: null,
            urls: {
                list: null,
                createItem: null,
                updateItem: null,
                deleteItem: null
            },
            labels: {
                noData: '',
                addDialogTitle: '',
                editDialogTitle: '',
                itemSaved: '',
                itemRemoved: '',
                deleteConfirmation: ''
            }
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.options.collection = this.options.collection || new NoteCollection();
            this.options.collection.baseUrl = this._getUrl('list');

            this.template = _.template($(this.options.template).html());

            this.listenTo(this.getCollection(), 'add', this._onItemAdded);
            this.listenTo(this.getCollection(), 'remove', this._onItemDeleted);
            this.listenTo(this.getCollection(), 'reset', this._onItemsAdded);

            var addItem = _.bind(this._addItem, this);
            mediator.once('hash_navigation_request:start', function () {
                mediator.off('note:add', addItem);
            });
            mediator.on('note:add', addItem);
        },

        render: function () {
            this.$el.append(this.template());
            this.$itemsContainer = this.$el.find('.items');
            this.$noDataContainer = this.$el.find('.no-data');
            this.$loadingMaskContainer = this.$el.find('.loading-mask');
            this.$itemsContainer.hide();

            return this;
        },

        getCollection: function () {
            return this.options.collection;
        },

        expandAll: function () {
            var $groups = this.$itemsContainer.find('.accordion-group');
            $groups.find('.accordion-toggle').removeClass('collapsed');
            $groups.find('.collapse').addClass('in');
        },

        collapseAll: function () {
            var $groups = this.$itemsContainer.find('.accordion-group');
            $groups.find('.accordion-toggle').addClass('collapsed');
            $groups.find('.collapse').removeClass('in');
        },

        refresh: function () {
            this._reload();
        },

        toggleSorting: function (e) {
            var $el = $(e.currentTarget),
                titleAlt = $el.data('title-alt'),
                iconAlt = $el.data('icon-alt');
            $el.data('title-alt', $el.attr('title'));
            $el.attr('title', titleAlt);
            $el.data('icon-alt', $el.find('i').attr('class').replace(/ hide-text/, ''));
            $el.find('i').attr('class', iconAlt + ' hide-text');

            this._reload(this.getCollection().getSorting() == 'DESC' ? 'ASC' : 'DESC');
        },

        _reload: function (sorting) {
            if (!_.isUndefined(sorting)) {
                this.getCollection().setSorting(sorting);
            }
            this._showLoading();
            try {
                this.getCollection().fetch({
                    reset: true,
                    success: _.bind(function () {
                        this._hideLoading();
                    }, this),
                    error: _.bind(function (collection, response) {
                        this._showLoadItemsError(response.responseJSON || {});
                    }, this)
                });
            } catch (err) {
                this._showLoadItemsError(err);
            }
        },

        _addItem: function () {
            this._openItemEditForm(this._getLabel('addDialogTitle'), this._getUrl('createItem'));
        },

        _editItem: function (itemView, model) {
            this._openItemEditForm(this._getLabel('editDialogTitle'), this._getUrl('updateItem', model));
        },

        _deleteItem: function (itemView, model) {
            var confirm = new DeleteConfirmation({
                content: this._getLabel('deleteConfirmation')
            });
            confirm.on('ok', _.bind(function () {
                this._onItemDelete(model);
            }, this));
            confirm.open();
        },

        _onItemsAdded: function (models) {
            if(models.length > 0){
                var collapsedItems = [];
                models.each(function (model) {
                    if (this._isItemViewCollapsed(this._findItemViewElement(model.id))) {
                        collapsedItems.push(model.id);
                    }
                }, this);
                this.$itemsContainer.empty();
                this._hideEmptyMessage();
                models.each(function (model) {
                    this.$itemsContainer.append(
                        this._renderItemView(model, _.indexOf(collapsedItems, model.id) != -1)
                    );
                }, this);
            } else {
                this.$itemsContainer.empty();
                this._showEmptyMessage();
            }
        },

        _onItemAdded: function (model) {
            if (this.getCollection().getSorting() == 'DESC') {
                this.$itemsContainer.prepend(this._renderItemView(model));
            } else {
                this.$itemsContainer.append(this._renderItemView(model));
            }
            this._hideEmptyMessage();
        },

        _onItemDelete: function (model) {
            this._showLoading();
            try {
                model.destroy({
                    wait: true,
                    url: this._getUrl('deleteItem', model),
                    success: _.bind(function () {
                        this._hideLoading();
                        messenger.notificationFlashMessage('success', this._getLabel('itemRemoved'));
                    }, this),
                    error: _.bind(function (model, response) {
                        if (!_.isUndefined(response.status) && response.status == 403) {
                            this._showForbiddenError(response.responseJSON || {});
                        } else {
                            this._showDeleteItemError(response.responseJSON || {});
                        }
                    }, this)
                });
            } catch (err) {
                this._showDeleteItemError(err);
            }
        },

        _onItemDeleted: function () {
            if(this.getCollection().length > 0){
                this._hideEmptyMessage();
            } else {
                this._showEmptyMessage();
            }
        },

        _createItemView: function (model) {
            var itemView = new NoteView({
                template: this.options.itemTemplate,
                id: this._buildItemIdAttribute(model.id),
                model: model
            });
            itemView.on('edit', _.bind(this._editItem, this));
            itemView.on('delete', _.bind(this._deleteItem, this));
            return itemView;
        },

        _renderItemView: function (model, collapsed) {
            return this._createItemView(model).render(collapsed).$el;
        },

        _findItemViewElement: function (id) {
            return this.$itemsContainer.find('#' + this._buildItemIdAttribute(id));
        },

        _isItemViewCollapsed: function (viewElement) {
            return viewElement.length > 0
                && viewElement.find('.accordion-toggle').hasClass('collapsed');
        },

        _getUrl: function (optionsKey) {
            if (_.isFunction(this.options.urls[optionsKey])) {
                return this.options.urls[optionsKey].apply(this, Array.prototype.slice.call(arguments, 1));
            }
            return this.options.urls[optionsKey];
        },

        _getLabel: function (labelKey) {
            return this.options.labels[labelKey];
        },

        _buildItemIdAttribute: function (id) {
            return 'note-' + id;
        },

        _showEmptyMessage: function () {
            this.$noDataContainer.show();
            this.$itemsContainer.hide();
        },

        _hideEmptyMessage: function() {
            this.$noDataContainer.hide();
            this.$itemsContainer.show();
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
                        'width': 675,
                        'autoResize': true,
                        'close': _.bind(function () {
                            delete this.itemEditDialog;
                        }, this)
                    }
                });
                this.itemEditDialog.render();
                mediator.once('hash_navigation_request:start', _.bind(function () {
                    if (this.itemEditDialog) {
                        this.itemEditDialog.remove();
                    }
                }, this));
                this.itemEditDialog.on('formSave', _.bind(function (response) {
                    this.itemEditDialog.remove();
                    delete this.itemEditDialog;
                    messenger.notificationFlashMessage('success', this._getLabel('itemSaved'));
                    var $itemView = this._findItemViewElement(response.id);
                    if ($itemView.length) {
                        var model = this.getCollection().get(response.id);
                        model.set(response);
                        $itemView.outerHTML(
                            this._renderItemView(model, this._isItemViewCollapsed($itemView))
                        );
                    } else {
                        this.getCollection().add(new NoteModel(response));
                    }
                }, this));
            }
        },

        _showLoading: function () {
            if (!this.$loadingMaskContainer.data('loading-mask-visible')) {
                this.loadingMask = new LoadingMask();
                this.$loadingMaskContainer.data('loading-mask-visible', true);
                this.$loadingMaskContainer.append(this.loadingMask.render().$el);
                this.loadingMask.show();
            }
        },

        _hideLoading: function () {
            if (this.loadingMask) {
                this.$loadingMaskContainer.data('loading-mask-visible', false);
                this.loadingMask.remove();
                this.loadingMask = null;
            }
        },

        _showLoadItemsError: function (err) {
            this._showError(__('Sorry, notes were not loaded correctly'), err);
        },

        _showDeleteItemError: function (err) {
            this._showError(__('Sorry, the note deleting was failed'), err);
        },

        _showForbiddenError: function (err) {
            this._showError(__('You do not have permission to perform this action.'), err);
        },

        _showError: function (message, err) {
            this._hideLoading();
            messenger.showErrorMessage(message, err);
        }
    });
});
