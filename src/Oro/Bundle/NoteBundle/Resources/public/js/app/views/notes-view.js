/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/app/views/base/collection-view',
    'oroui/js/mediator',
    'oroui/js/loading-mask',
    'oro/dialog-widget',
    'oroui/js/delete-confirmation'
], function ($, _, __, BaseCollectionView, mediator,
    LoadingMask, DialogWidget, DeleteConfirmation) {
    'use strict';

    var NotesView;

    NotesView = BaseCollectionView.extend({
        options: {
            template: null,
            itemTemplate: null,
            itemAddEvent: 'note:add',
            itemViewIdPrefix: 'note-',
            listSelector: '.items.list-box',
            fallbackSelector: '.no-data',
            loadingSelector: '.loading-mask',
            collection: null,
            urls: {
                createItem: null,
                updateItem: null,
                deleteItem: null
            },
            messages: {}
        },

        listen: {
            'toEdit collection': '_editItem',
            'toDelete collection': '_deleteItem'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            _.defaults(this.options.messages, {
                addDialogTitle: __('oro.note.add_note_title'),
                editDialogTitle: __('oro.note.edit_note_title'),
                itemSaved: __('oro.note.note_saved'),
                itemRemoved: __('oro.note.note_removed'),
                deleteConfirmation: __('oro.note.note_delete_confirmation'),
                loadItemsError: __('oro.note.load_notes_error'),
                deleteItemError: __('oro.note.delete_note_error'),
                forbiddenError: __('oro.note.forbidden_error')
            });

            this.template = _.template($(this.options.template).html());

            // create communication in scope of active controller
            mediator.on(this.options.itemAddEvent, this._addItem, this);

            NotesView.__super__.initialize.call(this, options);
        },

        render: function () {
            NotesView.__super__.render.apply(this, arguments);
            this.$loadingMaskContainer = this.$('.loading-mask');
            return this;
        },

        expandAll: function () {
            _.each(this.subviews, function (itemView) {
                itemView.toggle(false);
            });
        },

        collapseAll: function () {
            _.each(this.subviews, function (itemView) {
                itemView.toggle(true);
            });
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

            this._reload(this.collection.getSorting() === 'DESC' ? 'ASC' : 'DESC');
        },

        _reload: function (sorting) {
            var state = {};
            if (!_.isUndefined(sorting)) {
                this.collection.setSorting(sorting);
            }
            this._showLoading();
            try {
                _.each(this.subviews, function (itemView) {
                    state[itemView.model.get('id')] = itemView.isCollapsed();
                });
                this.collection.fetch({
                    reset: true,
                    success: _.bind(function () {
                        _.each(this.subviews, function (itemView) {
                            itemView.toggle(state[itemView.model.get('id')]);
                        });
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
            this._openItemEditForm(this._getMessage('addDialogTitle'), this._getUrl('createItem'));
        },

        _editItem: function (model) {
            this._openItemEditForm(this._getMessage('editDialogTitle'), this._getUrl('updateItem', model));
        },

        _deleteItem: function (model) {
            var confirm = new DeleteConfirmation({
                content: this._getMessage('deleteConfirmation')
            });
            confirm.on('ok', _.bind(function () {
                this._onItemDelete(model);
            }, this));
            confirm.open();
        },

        _onItemDelete: function (model) {
            this._showLoading();
            try {
                model.destroy({
                    wait: true,
                    url: this._getUrl('deleteItem', model),
                    success: _.bind(function () {
                        this._hideLoading();
                        mediator.execute('showFlashMessage', 'success', this._getMessage('itemRemoved'));
                    }, this),
                    error: _.bind(function (model, response) {
                        if (!_.isUndefined(response.status) && response.status === 403) {
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

        /**
         * Fetches url for certain action
         *
         * @param {string} actionKey
         * @param {Backbone.Model=}model
         * @returns {string}
         * @protected
         */
        _getUrl: function (actionKey, model) {
            if (_.isFunction(this.options.urls[actionKey])) {
                return this.options.urls[actionKey](model);
            }
            return this.options.urls[actionKey];
        },

        _getMessage: function (labelKey) {
            return this.options.messages[labelKey];
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
                mediator.once('page:request', _.bind(function () {
                    if (this.itemEditDialog) {
                        this.itemEditDialog.remove();
                    }
                }, this));
                this.itemEditDialog.on('formSave', _.bind(function (response) {
                    var model, insertPosition;
                    this.itemEditDialog.remove();
                    delete this.itemEditDialog;
                    mediator.execute('showFlashMessage', 'success', this._getMessage('itemSaved'));
                    model = this.collection.get(response.id);
                    if (model) {
                        model.set(response);
                    } else {
                        insertPosition = this.collection.sorting === 'DESC' ? 0 : this.collection.length;
                        this.collection.add(response, {at: insertPosition});
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
            this._showError(this.options.messages.loadItemsError, err);
        },

        _showDeleteItemError: function (err) {
            this._showError(this.options.messages.deleteItemError, err);
        },

        _showForbiddenError: function (err) {
            this._showError(this.options.messages.forbiddenError, err);
        },

        _showError: function (message, err) {
            this._hideLoading();
            mediator.execute('showErrorMessage', message, err);
        }
    });

    return NotesView;
});
