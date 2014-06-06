/*global define*/
define(['underscore', 'backbone', 'orotranslation/js/translator', 'oroui/js/app', 'oroui/js/messenger',
    'oroui/js/mediator', 'oronavigation/js/navigation', 'oroui/js/loading-mask', 'oro/dialog-widget', 'oroui/js/delete-confirmation',
    'oronote/js/note/view', 'oronote/js/note/collection', 'jquery-outer-html'],
function (
    _, Backbone, __, app, messenger,
    mediator, Navigation, LoadingMask, DialogWidget, DeleteConfirmation,
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
            'deleteItemUrl': null,
            'labels': {
                noData: '',
                addDialogTitle: '',
                editDialogTitle: '',
                itemSaved: '',
                deleteConfirmation: ''
            }
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.options.collection = this.options.collection || new NoteCollection();
            this.options.collection.baseUrl = this._getUrl('listUrl');

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

            if (!this.$el.find('.loading-mask').length) {
                this.$el.append($('<div class="loading-mask"></div>'));
            }
        },

        getCollection: function () {
            return this.options.collection;
        },

        reloadItems: function () {
            if (!_.isUndefined(arguments[0])) {
                this.getCollection().setSortMode(arguments[0]);
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

        addItem: function () {
            this._openItemEditForm(this._getLabel('addDialogTitle'), this._getUrl('createItemUrl'));
        },

        editItem: function (itemView, model) {
            this._openItemEditForm(this._getLabel('editDialogTitle'), this._getUrl('updateItemUrl', model));
        },

        deleteItem: function (itemView, model) {
            var confirm = new DeleteConfirmation({
                content: this._getLabel('deleteConfirmation')
            });
            confirm.on('ok', _.bind(function () {
                this._onItemDelete(model);
            }, this));
            confirm.open();
        },

        _onItemsAdded: function (models) {
            this.$itemsContainer.empty();
            if(models.length > 0){
                this._hideEmptyMessage();
                models.each(function (model) {
                    this._onItemAdded(model);
                }, this);
            } else {
                this._showEmptyMessage();
            }
        },

        _onItemAdded: function (model) {
            if (!this.$el.find('#' + this._buildItemIdAttribute(model.get('id'))).length) {
                this.$itemsContainer.append(this._renderItemView(model));
            }
        },

        _onItemDelete: function (model) {
            this._showLoading();
            try {
                var deleteUrl = this._getUrl('deleteItemUrl', model);
                model.destroy({
                    wait: true,
                    url: deleteUrl,
                    success: _.bind(function () {
                        this.reloadItems();
                    }, this),
                    error: _.bind(function (model, response) {
                        this._showDeleteItemError(response.responseJSON || {});
                    }, this)
                });
            } catch (err) {
                this._showDeleteItemError(err);
            }
        },

        _createItemView: function (model) {
            var itemView = new NoteView({
                template: '#template-note-item',
                buildItemIdAttribute: this._buildItemIdAttribute,
                deleteUrl: this.options['deleteItemUrl'],
                model: model
            });
            itemView.on('edit', _.bind(this.editItem, this));
            itemView.on('delete', _.bind(this.deleteItem, this));
            return itemView;
        },

        _renderItemView: function (model) {
            var $el = this._createItemView(model).render().$el;
            var navigation = Navigation.getInstance();
            if (navigation) {
                // trigger hash navigation event for processing UI decorators
                navigation.processClicks($el.find('a'));
            }
            return $el;
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
                mediator.once(
                    "hash_navigation_request:start",
                    _.bind(function () {
                        if (this.itemEditDialog) {
                            this.itemEditDialog.remove();
                        }
                    }, this)
                );
                this.itemEditDialog.on('formSave', _.bind(function (response) {
                    this.itemEditDialog.remove();
                    messenger.notificationFlashMessage('success', this._getLabel('itemSaved'));
                    var $itemView = this.$el.find('#' + this._buildItemIdAttribute(response.id));
                    if ($itemView.length) {
                        var model = this.getCollection().get(response.id);
                        model.set('message', response.message);
                        $itemView.outerHTML(this._renderItemView(model));
                    } else {
                        this.reloadItems();
                    }
                }, this));
            }
        },

        _showLoading: function () {
            var loadingElement = this.$el.find('.loading-mask');
            if (!loadingElement.data('loading-mask-visible')) {
                this.loadingMask = new LoadingMask();
                loadingElement.data('loading-mask-visible', true);
                loadingElement.append(this.loadingMask.render().$el);
                this.loadingMask.show();
            }
        },

        _hideLoading: function () {
            if (this.loadingMask) {
                var loadingElement = this.$el.find('.loading-mask');
                loadingElement.data('loading-mask-visible', false);
                this.loadingMask.remove();
                this.loadingMask = null;
            }
        },

        _showLoadItemsError: function (err) {
            this._showError(err, __('Sorry, notes were not loaded correctly'));
        },

        _showDeleteItemError: function (err) {
            this._showError(err, __('Sorry, the note deleting was failed'));
        },

        _showError: function (err, message) {
            this._hideLoading();
            if (!_.isUndefined(console)) {
                console.error(_.isUndefined(err.stack) ? err : err.stack);
            }
            var msg = message;
            if (app.debug) {
                if (!_.isUndefined(err.message)) {
                    msg += ': ' + err.message;
                } else if (!_.isUndefined(err.errors) && _.isArray(err.errors)) {
                    msg += ': ' + err.errors.join();
                } else if (_.isString(err)) {
                    msg += ': ' + err;
                }
            }
            messenger.notificationFlashMessage('error', msg);
        }
    });
});
