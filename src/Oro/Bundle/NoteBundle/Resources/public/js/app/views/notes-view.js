define(function(require) {
    'use strict';

    var NotesView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');
    var DialogWidget = require('oro/dialog-widget');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    NotesView = BaseCollectionView.extend({
        options: {
            template: null,
            itemTemplate: null,
            itemAddEvent: 'note:add',
            itemViewIdPrefix: 'note-',
            listSelector: '.items.list-box',
            fallbackSelector: '.no-data',
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

        /**
         * @inheritDoc
         */
        constructor: function NotesView() {
            NotesView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
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

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.itemEditDialog;
            NotesView.__super__.dispose.call(this);
        },

        expandAll: function() {
            _.each(this.getItemViews(), function(itemView) {
                itemView.toggle(false);
            });
        },

        collapseAll: function() {
            _.each(this.getItemViews(), function(itemView) {
                itemView.toggle(true);
            });
        },

        refresh: function() {
            this._reload();
        },

        toggleSorting: function(e) {
            var $el = $(e.currentTarget);
            var titleAlt = $el.data('title-alt');
            var iconAlt = $el.data('icon-alt');
            $el.data('title-alt', $el.attr('title'));
            $el.attr('title', titleAlt);
            $el.data('icon-alt', $el.find('i').attr('class').replace(/ hide-text/, ''));
            $el.find('i').attr('class', iconAlt + ' hide-text');

            this._reload(this.collection.getSorting() === 'DESC' ? 'ASC' : 'DESC');
        },

        _reload: function(sorting) {
            var state = {};
            if (!_.isUndefined(sorting)) {
                this.collection.setSorting(sorting);
            }
            this._showLoading();
            try {
                _.each(this.getItemViews(), function(itemView) {
                    state[itemView.model.get('id')] = itemView.isCollapsed();
                });
                this.collection.fetch({
                    reset: true,
                    success: _.bind(function() {
                        _.each(this.getItemViews(), function(itemView) {
                            itemView.toggle(state[itemView.model.get('id')]);
                        });
                        this._hideLoading();
                    }, this),
                    errorHandlerMessage: false,
                    error: _.bind(function(collection, response) {
                        this._showLoadItemsError(response.responseJSON || {});
                    }, this)
                });
            } catch (err) {
                this._showLoadItemsError(err);
            }
        },

        _addItem: function(e) {
            var url = this._getUrl('createItem');
            var routeAdditionalParams = $(e).data('route_additional_params') || {};

            if (!_.isEmpty(routeAdditionalParams)) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + $.param(routeAdditionalParams);
            }

            this._openItemEditForm(this._getMessage('addDialogTitle'), url);
        },

        _editItem: function(model) {
            this._openItemEditForm(this._getMessage('editDialogTitle'), this._getUrl('updateItem', model));
        },

        _deleteItem: function(model) {
            var confirm = new DeleteConfirmation({
                content: this._getMessage('deleteConfirmation')
            });
            confirm.on('ok', _.bind(function() {
                this._onItemDelete(model);
            }, this));
            confirm.open();
        },

        _onItemDelete: function(model) {
            this._showLoading();
            try {
                model.destroy({
                    wait: true,
                    url: this._getUrl('deleteItem', model),
                    success: _.bind(function() {
                        this._hideLoading();
                        mediator.execute('showFlashMessage', 'success', this._getMessage('itemRemoved'));
                    }, this),
                    errorHandlerMessage: false,
                    error: _.bind(function(model, response) {
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
        _getUrl: function(actionKey, model) {
            if (_.isFunction(this.options.urls[actionKey])) {
                return this.options.urls[actionKey](model);
            }
            return this.options.urls[actionKey];
        },

        _getMessage: function(labelKey) {
            return this.options.messages[labelKey];
        },

        _openItemEditForm: function(title, url) {
            if (!this.itemEditDialog) {
                this.itemEditDialog = new DialogWidget({
                    url: url,
                    title: title,
                    regionEnabled: false,
                    incrementalPosition: false,
                    dialogOptions: {
                        modal: true,
                        resizable: false,
                        width: 675,
                        autoResize: true,
                        close: _.bind(function() {
                            delete this.itemEditDialog;
                        }, this)
                    }
                });
                this.itemEditDialog.render();
                this.itemEditDialog.on('formSave', _.bind(function(response) {
                    this.itemEditDialog.remove();
                    mediator.execute('showFlashMessage', 'success', this._getMessage('itemSaved'));
                    var insertPosition;
                    var model = this.collection.get(response.id);
                    if (model) {
                        model.set(response);
                    } else {
                        insertPosition = this.collection.sorting === 'DESC' ? 0 : this.collection.length;
                        this.collection.add(response, {at: insertPosition});
                    }
                }, this));
            }
        },

        /**
         * Shows loading indicator
         */
        _showLoading: function() {
            this.subview('loadingMask', new LoadingMask({
                container: this.$el
            }));
            this.subview('loadingMask').show();
        },

        /**
         * Hides loading indicator
         */
        _hideLoading: function() {
            this.removeSubview('loadingMask');
        },

        _showLoadItemsError: function(err) {
            this._showError(this.options.messages.loadItemsError, err);
        },

        _showDeleteItemError: function(err) {
            this._showError(this.options.messages.deleteItemError, err);
        },

        _showForbiddenError: function(err) {
            this._showError(this.options.messages.forbiddenError, err);
        },

        _showError: function(message, err) {
            this._hideLoading();
            mediator.execute('showErrorMessage', message, err);
        }
    });

    return NotesView;
});
