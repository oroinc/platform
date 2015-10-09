define([
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    './collection',
    './model',
    './view-name-modal',
    'oroui/js/mediator',
    'oroui/js/delete-confirmation'
], function(Backbone, _, __, GridViewsCollection, GridViewModel, ViewNameModal, mediator, DeleteConfirmation) {
    'use strict';

    var GridViewsView;
    var $ = Backbone.$;

    /**
     * Datagrid views widget
     *
     * @export  orodatagrid/js/datagrid/grid-views/view
     * @class   orodatagrid.datagrid.GridViewsView
     * @extends Backbone.View
     */
    GridViewsView = Backbone.View.extend({
        DEFAULT_GRID_VIEW_ID: '__all__',

        className: 'grid-views',

        /** @property */
        events: {
            'click .views-group a': 'onChange',
            'click a.save': 'onSave',
            'click a.save_as': 'onSaveAs',
            'click a.share': 'onShare',
            'click a.unshare': 'onUnshare',
            'click a.delete': 'onDelete',
            'click a.rename': 'onRename',
            'click a.discard_changes': 'onDiscardChanges'
        },

        /** @property */
        template: null,

        /** @property */
        titleTemplate: null,

        /** @property */
        title: null,

        /** @property */
        enabled: true,

        /** @property */
        choices: [],

        /** @property */
        permissions: {
            CREATE: false,
            EDIT: false,
            DELETE: false,
            SHARE: false,
            EDIT_SHARED: false
        },

        /** @property */
        prevState: {},

        /** @property */
        gridName: {},

        /** @property */
        viewsCollection: GridViewsCollection,

        /** @property */
        originalTitle: null,

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Boolean} [options.enable]
         * @param {Array}   [options.choices]
         * @param {Array}   [options.views]
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            this.template = _.template($('#template-datagrid-grid-view').html());
            this.titleTemplate = _.template($('#template-datagrid-grid-view-label').html());

            if (options.choices) {
                this.choices = _.union(this.choices, options.choices);
                if (!this._getView(this.DEFAULT_GRID_VIEW_ID).label) {
                    this._getView(this.DEFAULT_GRID_VIEW_ID).label =
                        __('oro.datagrid.gridView.all') + (options.title || '');
                }
            }

            if (options.permissions) {
                this.permissions = _.extend(this.permissions, options.permissions);
            }

            if (options.title) {
                this.title = options.title;
            }

            this.originalTitle = $('head title').text();

            this.gridName = options.gridName;
            this.collection = options.collection;
            this.enabled = options.enable !== false;

            options.views = options.views || [];
            _.each(options.views, function(view) {
                view.grid_name = this.gridName;
            }, this);

            this.viewsCollection = new this.viewsCollection(options.views);
            if (!this.collection.state.gridView) {
                this.collection.state.gridView = this.DEFAULT_GRID_VIEW_ID;
            }
            this.viewsCollection.get(this.DEFAULT_GRID_VIEW_ID).set({
                filters: options.collection.initialState.filters,
                sorters: options.collection.initialState.sorters
            });

            this.viewDirty = !this._isCurrentStateSynchronized();
            this.prevState = this._getCurrentState();

            this._bindEventListeners();
            this._updateTitle();

            GridViewsView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.viewsCollection.dispose();
            delete this.viewsCollection;
            GridViewsView.__super__.dispose.call(this);
        },

        _bindEventListeners: function() {
            this.listenTo(this.collection, 'updateState', function(collection) {
                if (!collection.state.gridView) {
                    collection.state.gridView = this.DEFAULT_GRID_VIEW_ID;
                }
            });
            this.listenTo(this.collection, 'updateState', this.render);
            this.listenTo(this.collection, 'beforeFetch', this.render);
            this.listenTo(this.collection, 'reset', this._onCollectionReset);
            this.listenTo(this.collection, 'reset', this.render);

            this.listenTo(this.viewsCollection, 'add', this._onModelAdd);
            this.listenTo(this.viewsCollection, 'remove', this._onModelRemove);
            this.listenTo(this.viewsCollection, 'change', this._onModelChange, this);

            this.listenTo(mediator, 'datagrid:' + this.gridName + ':views:add', function(model) {
                this.viewsCollection.add(model);
            }, this);
            this.listenTo(mediator, 'datagrid:' + this.gridName + ':views:remove', function(model) {
                this.viewsCollection.remove(model);
            }, this);
            this.listenTo(mediator, 'datagrid' + this.gridName + ':views:change', function(model) {
                this.viewsCollection.get(model).attributes = model.attributes;
                this._getView(model.get('name')).label = model.get('label');
                this.viewDirty = !this._isCurrentStateSynchronized();
                this.render();
            }, this);
        },

        /**
         * Disable view selector
         *
         * @return {*}
         */
        disable: function() {
            this.enabled = false;
            this.render();

            return this;
        },

        /**
         * Enable view selector
         *
         * @return {*}
         */
        enable: function() {
            this.enabled = true;
            this.render();

            return this;
        },

        /**
         * Select change event handler
         *
         * @param {Event} e
         */
        onChange: function(e) {
            e.preventDefault();
            var value = $(e.target).data('value');
            this.changeView(value);
            this._updateTitle();

            this.prevState = this._getCurrentState();
            this.viewDirty = !this._isCurrentStateSynchronized();
        },

        /**
         * @param {Event} e
         */
        onSave: function(e) {
            var model = this._getCurrentViewModel();
            var self = this;

            model.save({
                label: model.get('label'),
                filters: this.collection.state.filters,
                sorters: this.collection.state.sorters
            }, {
                wait: true,
                success: function() {
                    self._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            });
        },

        /**
         * @param {Event} e
         */
        onSaveAs: function(e) {
            var modal = new ViewNameModal();

            var self = this;
            modal.on('ok', function(e) {
                var model = self._createViewModel({
                    label: this.$('input[name=name]').val(),
                    type: 'private',
                    grid_name: self.gridName,
                    filters: self.collection.state.filters,
                    sorters: self.collection.state.sorters,
                    editable: self.permissions.EDIT,
                    deletable: self.permissions.DELETE
                });
                model.save(null, {
                    wait: true,
                    success: function(model) {
                        model.set('name', model.get('id'));
                        model.unset('id');
                        self.viewsCollection.add(model);
                        self.changeView(model.get('name'));
                        self.collection.state.gridView = model.get('name');
                        self.viewDirty = !self._isCurrentStateSynchronized();
                        self._updateTitle();
                        self._showFlashMessage('success', __('oro.datagrid.gridView.created'));
                        mediator.trigger('datagrid:' + self.gridName + ':views:add', model);
                    },
                    error: function(model, response, options) {
                        modal.open();
                        self._showNameError(modal, response);
                    }
                });
            });

            modal.open();
            $('#gridViewName').focus();
        },

        /**
         * @param {Event} e
         */
        onShare: function(e) {
            var model = this._getCurrentViewModel();
            var self = this;

            model.save({
                label: model.get('label'),
                type: 'public'
            }, {
                wait: true,
                success: function() {
                    self._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            });
        },

        /**
         * @param {Event} e
         */
        onUnshare: function(e) {
            var model = this._getCurrentViewModel();
            var self = this;

            model.save({
                label: model.get('label'),
                type: 'private'
            }, {
                wait: true,
                success: function() {
                    self._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            });
        },

        /**
         * @param {Event} e
         */
        onDelete: function(e) {
            var id = this._getCurrentView().value;
            var model = this.viewsCollection.get(id);

            var confirm = new DeleteConfirmation({
                content: __('Are you sure you want to delete this item?')
            });
            confirm.on('ok', _.bind(function() {
                model.destroy({wait: true});
                model.once('sync', function() {
                    this._showFlashMessage('success', __('oro.datagrid.gridView.deleted'));
                    mediator.trigger('datagrid:' + this.gridName + ':views:remove', model);
                }, this);
            }, this));

            confirm.open();
        },

        /**
         * @param {Event} e
         */
        onRename: function(e) {
            var model = this._getCurrentViewModel();
            var self = this;

            var modal = new ViewNameModal({
                defaultValue: model.get('label')
            });
            modal.on('ok', function() {
                model.save({
                    label: this.$('input[name=name]').val()
                }, {
                    wait: true,
                    success: function() {
                        self._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                    },
                    error: function(model, response, options) {
                        modal.open();
                        self._showNameError(modal, response);
                    }
                });
            });

            modal.open();
        },

        /**
         * @param {Event} e
         */
        onDiscardChanges: function(e) {
            this.changeView(this.collection.state.gridView);
        },

        /**
         * @private
         *
         * @param {GridViewModel} model
         */
        _onModelAdd: function(model) {
            this.choices.push({
                label: model.get('label'),
                value: model.get('name')
            });
            this.render();
        },

        /**
         * @private
         *
         * @param {GridViewModel} model
         */
        _onModelRemove: function(model) {
            var viewId = this.collection.state.gridView;
            viewId = viewId === this.DEFAULT_GRID_VIEW_ID ? viewId : parseInt(viewId, 10);

            this.choices = _.reject(this.choices, function(item) {
                return item.value === viewId;
            }, this);

            if (model.id === viewId) {
                this.collection.state.gridView = this.DEFAULT_GRID_VIEW_ID;
                this.viewDirty = !this._isCurrentStateSynchronized();
            }

            this.render();
        },

        /**
         * @private
         *
         * @param {GridViewModel} model
         */
        _onModelChange: function(model) {
            mediator.trigger('datagrid' + this.gridName + ':views:change', model);
        },

        /**
         * @private
         */
        _onCollectionReset: function() {
            var newState = this._getCurrentState();
            if (_.isEqual(newState, this.prevState)) {
                return;
            }

            this.viewDirty = !this._isCurrentStateSynchronized();
            this.prevState = newState;
        },

        /**
         * Updates collection
         *
         * @param gridView
         * @returns {*}
         */
        changeView: function(gridView) {
            var viewState;
            var view = this.viewsCollection.get(gridView);

            if (view) {
                viewState = _.extend({}, this.collection.initialState, view.toGridState());
                this.collection.updateState(viewState);
                this.collection.fetch({reset: true});
            }

            return this;
        },

        render: function(o) {
            var html;
            this.$el.empty();

            var title = this.titleTemplate({
                title: this._getCurrentViewLabel(),
                navbar: Boolean(this.title)
            });

            var actions = this._getCurrentActions();
            html = this.template({
                title: title,
                titleLabel: this.title,
                disabled: !this.enabled,
                choices: this.choices,
                dirty: this.viewDirty,
                editedLabel: __('oro.datagrid.gridView.data_edited'),
                actionsLabel: __('oro.datagrid.gridView.actions'),
                actions: actions,
                showActions: _.some(actions, function(action) {
                    return action.enabled;
                })
            });

            this.$el.append(html);

            return this;
        },

        /**
         * @private
         *
         * @returns {Array}
         */
        _getCurrentActions: function() {
            var currentView = this._getCurrentViewModel();

            return [
                {
                    label: __('oro.datagrid.action.save_grid_view'),
                    name: 'save',
                    enabled: this.viewDirty &&
                            typeof currentView !== 'undefined' &&
                            currentView.get('editable') &&
                            (
                                currentView.get('type') === 'private' ||
                                (
                                   currentView.get('type') === 'public' &&
                                   this.permissions.EDIT_SHARED
                                   )
                            )
                },
                {
                    label: __('oro.datagrid.action.save_grid_view_as'),
                    name: 'save_as',
                    enabled: this.permissions.CREATE
                },
                {
                    label: __('oro.datagrid.action.rename_grid_view'),
                    name: 'rename',
                    enabled: typeof currentView !== 'undefined' &&
                            currentView.get('editable') &&
                            (
                                currentView.get('type') === 'private' ||
                                (
                                    currentView.get('type') === 'public' &&
                                    this.permissions.EDIT_SHARED
                                    )
                                )
                },
                {
                    label: __('oro.datagrid.action.share_grid_view'),
                    name: 'share',
                    enabled: typeof currentView !== 'undefined' &&
                            currentView.get('type') === 'private' &&
                            this.permissions.SHARE
                },
                {
                    label: __('oro.datagrid.action.unshare_grid_view'),
                    name: 'unshare',
                    enabled: typeof currentView !== 'undefined' &&
                            currentView.get('editable') &&
                            currentView.get('type') === 'public' &&
                            this.permissions.EDIT_SHARED
                },
                {
                    label: __('oro.datagrid.action.discard_grid_view_changes'),
                    name: 'discard_changes',
                    enabled: this.viewDirty
                },
                {
                    label: __('oro.datagrid.action.delete_grid_view'),
                    name: 'delete',
                    enabled: typeof currentView !== 'undefined' &&
                            currentView.get('deletable')
                }
            ];
        },

        /**
         * @protected
         *
         * @param   {Object} data
         * @returns {GridViewModel}
         */
        _createViewModel: function(data) {
            return new GridViewModel(data);
        },

        /**
         * @private
         *
         * @returns {undefined|GridViewModel}
         */
        _getCurrentViewModel: function() {
            if (!this._hasActiveView()) {
                return;
            }

            return this.viewsCollection.findWhere({
                name: this._getCurrentView().value
            });
        },

        /**
         * @private
         *
         * @returns {boolean}
         */
        _hasActiveView: function() {
            return typeof this._getCurrentView() !== 'undefined';
        },

        /**
         * @private
         *
         * @returns {string}
         */
        _getCurrentViewLabel: function() {
            var currentView = this._getCurrentView();

            if (typeof currentView === 'undefined') {
                return this.title ? this.title : __('Please select view');
            }

            return currentView.label;
        },

        /**
         * @private
         *
         * @param {String} name
         * @returns {undefined|Object}
         */
        _getView: function(name) {
            var currentViews =  _.filter(this.choices, function(item) {
                return item.value === name;
            }, this);

            return _.first(currentViews);
        },

        /**
         * @private
         *
         * @returns {undefined|Object}
         */
        _getCurrentView: function() {
            return this._getView(this.collection.state.gridView);
        },

        /**
         * @private
         *
         * @returns {Boolean}
         */
        _isCurrentStateSynchronized: function() {
            var modelState = this._getCurrentViewModelState();
            if (!modelState) {
                return true;
            }

            return _.isEqual(this._getCurrentState(), modelState);
        },

        /**
         * @private
         *
         * @returns {Object|undefined}
         */
        _getCurrentViewModelState: function() {
            var model = this._getCurrentViewModel();
            if (!model) {
                return;
            }

            return {
                filters: model.get('filters'),
                sorters: model.get('sorters')
            };
        },

        /**
         * @private
         *
         * @returns {Object}
         */
        _getCurrentState: function() {
            return {
                filters: this.collection.state.filters,
                sorters: this.collection.state.sorters
            };
        },

        /**
         * @private
         *
         * @returns {String}
         */
        _createTitle: function() {
            var currentView = this._getCurrentView();
            if (!currentView) {
                return this.originalTitle;
            }

            var title = currentView.label;
            if (currentView.value === '__all__') {
                title = __('oro.datagrid.gridView.all');
            }

            return title + ' - ' + this.originalTitle;
        },

        /**
         * @private
         *
         * Takes the same arguments as showFlashMessage command
         */
        _showFlashMessage: function(type, message, options) {
            var opts = options || {};
            var id = this.$el.closest('.ui-widget-content').attr('id');

            if (id) {
                opts = _.extend(opts, {
                    container: '#' + id + ' .flash-messages'
                });
            }

            mediator.execute('showFlashMessage', type, message, opts);
        },

        /**
         * @private
         */
        _showNameError: function(modal, response) {
            if (response.status === 400) {
                var jsonResponse = JSON.parse(response.responseText);
                var errors = jsonResponse.errors.children.label.errors;
                if (errors) {
                    modal.setNameError(_.first(errors));
                }
            }
        },

        /**
         * @private
         */
        _updateTitle: function() {
            if (!this.title) {
                return;
            }

            var newTitle = this._createTitle();
            mediator.execute('adjustTitle', newTitle, true);
        }
    });

    return GridViewsView;
});
