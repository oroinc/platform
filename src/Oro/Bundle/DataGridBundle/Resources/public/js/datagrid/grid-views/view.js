define(function(require) {
    'use strict';

    var GridViewsView;
    var template = require('tpl!orodatagrid/templates/datagrid/grid-view.html');
    var titleTemplate = require('tpl!orodatagrid/templates/datagrid/grid-view-label.html');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var GridViewModel = require('./model');
    var ViewNameModal = require('./view-name-modal');
    var mediator = require('oroui/js/mediator');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var routing = require('routing');

    /**
     * Datagrid views widget
     *
     * @export  orodatagrid/js/datagrid/grid-views/view
     * @class   orodatagrid.datagrid.GridViewsView
     * @extends BaseView
     */
    GridViewsView = BaseView.extend({
        /** @property */
        DEFAULT_GRID_VIEW_ID: '__all__',

        /** @property */
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
            'click a.discard_changes': 'onDiscardChanges',
            'click a.use_as_default': 'onUseAsDefault'
        },

        /** @property */
        template: template,

        /** @property */
        titleTemplate: titleTemplate,

        /** @property */
        title: null,

        /** @property */
        enabled: true,

        /** @property */
        appearances: null,

        /** @property */
        permissions: {
            CREATE: false,
            EDIT: false,
            DELETE: false,
            SHARE: false
        },

        /** @property */
        prevState: {},

        /** @property */
        gridName: {},

        /** @type {GridViewsCollection} */
        viewsCollection: null,

        /** @property */
        originalTitle: null,

        /** @property */
        defaultPrefix: __('oro.datagrid.gridView.all'),

        /** @property */
        route: 'oro_datagrid_api_rest_gridview_default',

        /** @property */
        DeleteConfirmation: DeleteConfirmation,

        /** @property */
        defaults: {
            DeleteConfirmationOptions: {
                content: __('Are you sure you want to delete this item?')
            }
        },

        /** @property */
        modal: null,

        /** @property */
        showErrorMessage: true,

        /** @property */
        adjustDocumentTitle: true,

        /**
         * @inheritDoc
         */
        constructor: function GridViewsView() {
            GridViewsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Boolean} [options.enable]
         * @param {string}  [options.title]
         * @param {GridViewsCollection} [options.viewsCollection]
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            if (!options.viewsCollection) {
                throw new TypeError('"viewsCollection" is required');
            }

            _.extend(this, _.pick(options, ['viewsCollection', 'title', 'appearances']));

            this.template = this.getTemplateFunction();
            this.titleTemplate = this.getTemplateFunction('titleTemplate');

            if (options.permissions) {
                this.permissions = _.extend(this.permissions, options.permissions);
            }

            this.originalTitle = $('head title').text();

            this.gridName = options.gridName;
            this.collection = options.collection;
            this.enabled = options.enable !== false;

            if (!this.collection.state.gridView) {
                this.collection.state.gridView = this.DEFAULT_GRID_VIEW_ID;
            }
            this.viewsCollection.get(this.DEFAULT_GRID_VIEW_ID).set({
                filters: options.collection.initialState.filters,
                sorters: options.collection.initialState.sorters,
                columns: options.collection.initialState.columns,
                appearanceType: options.collection.initialState.appearanceType,
                appearanceData: options.collection.initialState.appearanceData
            });

            this.viewDirty = !this._isCurrentStateSynchronized();
            this.prevState = this._getCurrentViewModelState();

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
            this.listenTo(this.collection, 'reset', this.render);

            this.listenTo(this.viewsCollection, 'add', this._onModelAdd);
            this.listenTo(this.viewsCollection, 'remove', this._onModelRemove);
            this.listenTo(this.viewsCollection, 'change', this._onModelChange, this);
            this.listenTo(this.viewsCollection, 'sync', this._onModelChange, this);

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

            this.listenTo(mediator, this.gridName + ':grid-views-model:invalid', function(params) {
                this.onGridViewsModelInvalid(params);
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
            var value = $(e.currentTarget).data('value');
            this.changeView(value);
            this._updateTitle();

            this.prevState = this._getCurrentState();
            this.viewDirty = !this._isCurrentStateSynchronized();
        },

        /**
         * @param {Event} e
         */
        onSave: function(e) {
            var model = this._getEditableViewModel(e.currentTarget);

            this._onSaveModel(model);
        },

        _onSaveModel: function(model) {
            var self = this;

            model.save({
                icon: void 0,
                label: model.get('label'),
                filters: this.collection.state.filters,
                sorters: this.collection.state.sorters,
                columns: this.collection.state.columns,
                appearanceType: this.collection.state.appearanceType,
                appearanceData: this.collection.state.appearanceData
            }, {
                wait: true,
                errorHandlerMessage: self.showErrorMessage,
                success: function() {
                    self._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            });
        },

        onSaveAs: function() {
            var modal = new ViewNameModal();
            var self = this;

            modal.on('ok', function() {
                var data = self.getInputData(modal.$el);
                var model = self._createBaseViewModel(data);

                if (model.isValid()) {
                    self.lockModelOnOkCloses(modal, true);
                    self._onSaveAsModel(model);
                } else {
                    self.lockModelOnOkCloses(modal, false);
                }
            });

            modal.open();
            modal.$el.find('[data-role="grid-view-input"]').focus();

            this.modal = modal;
        },

        /**
         * @param {Object} model
         * @private
         */
        _onSaveAsModel: function(model) {
            var self = this;

            model.save(null, {
                wait: true,
                success: function(model) {
                    var currentModel = self._getCurrentDefaultViewModel();
                    var icon = self._getAppearanceIcon(model.get('appearanceType'));
                    model.set('name', model.get('id'));
                    model.set('icon', icon);
                    model.unset('id');
                    if (model.get('is_default') && currentModel) {
                        currentModel.set({is_default: false});
                    }
                    self.viewsCollection.add(model);
                    self.changeView(model.get('name'));
                    self.collection.state.gridView = model.get('name');
                    self.viewDirty = !self._isCurrentStateSynchronized();
                    self._updateTitle();
                    self._showFlashMessage('success', __('oro.datagrid.gridView.created'));
                    mediator.trigger('datagrid:' + self.gridName + ':views:add', model);
                },
                errorHandlerMessage: self.showErrorMessage,
                error: function(model, response, options) {
                    self.onError(model, response, options);
                }
            });
        },

        _getAppearanceIcon: function(appearanceType) {
            return this.appearances ? _.result(_.findWhere(this.appearances, {type: appearanceType}), 'icon') : '';
        },

        /**
         * @param {Event} e
         */
        onShare: function(e) {
            var model = this._getEditableViewModel(e.currentTarget);
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
            var model = this._getEditableViewModel(e.currentTarget);
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
            var model = this._getModelForDelete(e.currentTarget);

            var confirm = new this.DeleteConfirmation(this.defaults.DeleteConfirmationOptions);
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
         * @param {HTML} element
         */
        _getModelForDelete: function(element) {
            // Accepts a element, that is can used for users extends
            var id = this._getCurrentView().value;

            return this.viewsCollection.get(id);
        },

        /**
         * @param {Event} e
         */
        onRename: function(e) {
            var self = this;
            var model = this._getEditableViewModel(e.currentTarget);
            var modal = new ViewNameModal({
                defaultValue: model.get('label'),
                defaultChecked: model.get('is_default')
            });

            modal.on('ok', function() {
                var data = self.getInputData(modal.$el);

                model.set(data, {silent: true});

                if (model.isValid()) {
                    self.lockModelOnOkCloses(modal, true);
                    self._onRenameSaveModel(model);
                } else {
                    self.lockModelOnOkCloses(modal, false);
                }
            });
            modal.open();
            this.modal = modal;
        },

        /**
         * @param {object} model
         * @private
         */
        _onRenameSaveModel: function(model) {
            var self = this;

            model.save(
                null, {
                    wait: true,
                    success: function(savedModel) {
                        var currentDefaultViewModel = self._getCurrentDefaultViewModel();
                        var isCurrentDefault = currentDefaultViewModel === model;
                        var isCurrentWasDefault = currentDefaultViewModel === undefined;
                        if (model.get('is_default') && !isCurrentDefault) {
                            // if current view hadn't default property and it is going to be
                            currentDefaultViewModel.set({is_default: false});
                        } else if (isCurrentWasDefault) {
                            // if current view had 'default' property and this property was removed, there are no
                            // views with 'default' property and it shall be set to system view.
                            self._getDefaultSystemViewModel().set({is_default: true});
                        }

                        model.set({
                            label: savedModel.get('label')
                        });

                        self._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                    },
                    errorHandlerMessage: self.showErrorMessage,
                    error: function(model, response, options) {
                        model.set('label', model.previous('label'));
                        self.onError(model, response, options);
                    }
                });
        },

        onError: function(model, response, options) {
            if (_.isObject(this.modal)) {
                this.modal.open();
            }
            this._showNameError(this.modal, response);
        },

        /**
         * @param {array} errors
         */
        onGridViewsModelInvalid: function(errors) {
            if (errors && _.isObject(this.modal)) {
                this.modal.setNameError(_.first(errors));
                this.modal.open();
            }
        },

        /**
         *
         * @param {object} modal
         * @param {boolean} lock
         */
        lockModelOnOkCloses: function(modal, lock) {
            if (_.isObject(modal) && _.isObject(modal.options)) {
                modal.options.okCloses = lock;
            }
        },

        /**
         * @param {Event} e
         */
        onDiscardChanges: function(e) {
            this.changeView(this.collection.state.gridView);
        },

        /**
         * Prepares choice items for grid view dropdown
         *
         * @return {Array<{label:{string},icon:{string},value:{*}}>}
         */
        getViewChoices: function() {
            var showIcons = _.uniq(this.viewsCollection.pluck('icon')).length > 1;
            var choices = this.viewsCollection.map(function(model) {
                return {
                    label: model.getLabel(),
                    icon: showIcons ? model.get('icon') : false,
                    value: model.get('name')
                };
            });

            var defaultItem = _.findWhere(choices, {value: this.DEFAULT_GRID_VIEW_ID});
            if (defaultItem.label === this.DEFAULT_GRID_VIEW_ID) {
                defaultItem.label = this.defaultPrefix + (this.title || '');
            }

            return choices;
        },

        /**
         * @param {Event} e
         */
        onUseAsDefault: function(e) {
            var self = this;
            var isDefault = 1;
            var defaultModel = this._getCurrentDefaultViewModel();
            var gridName = this.gridName;
            var currentViewModel = this._getEditableViewModel(e.currentTarget);
            var id = currentViewModel.id;
            if (this._isCurrentViewSystem()) {
                // in this case we need to set default to false on current default view
                isDefault = 0;
                if (defaultModel) {
                    id = defaultModel.id;
                }
            }
            return $.post(
                routing.generate(this.route, {
                    'id': id,
                    'default': isDefault,
                    'gridName': gridName
                }),
                {},
                function(response) {
                    if (defaultModel) {
                        defaultModel.set({is_default: false});
                    }
                    currentViewModel.set({is_default: true});
                    self._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            );
        },

        /**
         * @private
         *
         * @param {GridViewModel} model
         */
        _onModelAdd: function() {
            this.render();
        },

        /**
         * @private
         *
         * @param {GridViewModel} model
         */
        _onModelRemove: function(model) {
            this.collection.state.gridView = this.DEFAULT_GRID_VIEW_ID;

            var systemModel = this._getDefaultSystemViewModel();
            if (model.get('is_default')) {
                systemModel.set({is_default: true});
            }

            this.changeView(systemModel);
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
        _checkCurrentState: function() {
            this.viewDirty = !this._isCurrentStateSynchronized();
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

            this._checkCurrentState();

            var title = this.renderTitle();

            var actions = this._getViewActions();
            html = this.template({
                title: title,
                titleLabel: this.title,
                disabled: !this.enabled,
                choices: this.getViewChoices(),
                current: this.collection.state.gridView,
                dirty: this.viewDirty,
                editedLabel: __('oro.datagrid.gridView.data_edited'),
                actionsLabel: __('oro.datagrid.gridView.actions'),
                actions: actions,
                showActions: this.showActions(actions),
                gridViewId: this.cid
            });

            this.$el.append(html);

            return this;
        },

        /**
         * @returns {HTMLElement}
         */
        renderTitle: function() {
            return this.titleTemplate({
                title: this._getCurrentViewLabel(),
                navbar: Boolean(this.title)
            });
        },

        /**
         * @returns {*|Array}
         * @private
         */
        _getViewActions: function() {
            return this._getCurrentActions();
        },

        /**
         * @param actions
         * @returns {boolean}
         */
        showActions: function(actions) {
            return _.some(actions, function(action) {
                return action.enabled;
            });
        },

        /**
         * @private
         *
         * @returns {Array}
         */
        _getCurrentActions: function() {
            var currentGridView = this._getCurrentViewModel();

            return this._getActions(currentGridView);
        },

        /**
         * @param GridView
         * @returns {*[]}
         * @private
         */
        _getActions: function(GridView) {
            var currentDefaultView = this._getCurrentDefaultViewModel();

            return [
                {
                    label: __('oro.datagrid.action.save_grid_view'),
                    name: 'save',
                    enabled: this._getViewIsDirty(GridView) &&
                             typeof GridView !== 'undefined' &&
                            GridView.get('editable')
                },
                {
                    label: __('oro.datagrid.action.save_grid_view_as'),
                    name: 'save_as',
                    enabled: this.permissions.CREATE
                },
                {
                    label: __('oro.datagrid.action.rename_grid_view'),
                    name: 'rename',
                    enabled: typeof GridView !== 'undefined' &&
                        GridView.get('editable')
                },
                {
                    label: __('oro.datagrid.action.share_grid_view'),
                    name: 'share',
                    enabled: typeof GridView !== 'undefined' &&
                            GridView.get('type') === 'private' &&
                             this.permissions.SHARE
                },
                {
                    label: __('oro.datagrid.action.unshare_grid_view'),
                    name: 'unshare',
                    enabled: typeof GridView !== 'undefined' &&
                            GridView.get('editable') &&
                            GridView.get('type') === 'public' &&
                            this.permissions.SHARE
                },
                {
                    label: __('oro.datagrid.action.discard_grid_view_changes'),
                    name: 'discard_changes',
                    enabled: this._getViewIsDirty(GridView)
                },
                {
                    label: __('oro.datagrid.action.delete_grid_view'),
                    name: 'delete',
                    enabled: typeof GridView !== 'undefined' &&
                        GridView.get('deletable')
                },
                {
                    label: __('oro.datagrid.action.set_as_default_grid_view'),
                    name: 'use_as_default',
                    enabled: typeof GridView !== 'undefined' &&
                            !GridView.get('is_default') &&
                            (!this._isCurrentViewSystem() || currentDefaultView)
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
         * Create GridView model with basic properties
         * @protected
         *
         * @param   {Object} data
         * @returns {GridViewModel}
         */
        _createBaseViewModel: function(data) {
            return this._createViewModel(
                {
                    label: _.isUndefined(data.label) ? this.defaultPrefix : data.label,
                    is_default: _.isUndefined(data.is_default) ? false : data.is_default,
                    type: 'private',
                    grid_name: this.gridName,
                    filters: this.collection.state.filters,
                    sorters: this.collection.state.sorters,
                    columns: this.collection.state.columns,
                    appearanceType: this.collection.state.appearanceType,
                    appearanceData: this.collection.state.appearanceData,
                    editable: this.permissions.EDIT,
                    deletable: this.permissions.DELETE,
                    freezeName: this.defaultPrefix + (this.title || '')
                }
            );
        },

        /**
         * @param {object} GridView
         * @returns {boolean|*}
         * @private
         */
        _getViewIsDirty: function(GridView) {
            // Accepts a GridView, that is can used for users extends
            return this.viewDirty;
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
         * @params {HTML} element
         * @private
         *
         * @returns {undefined|GridViewModel}
         */
        _getEditableViewModel: function(element) {
            // Accepts a element, that is can used for users extends
            return this._getCurrentViewModel();
        },

        /**
         * @private
         *
         * @returns {undefined|GridViewModel}
         */
        _getCurrentDefaultViewModel: function() {
            if (!this._hasActiveView()) {
                return;
            }

            return this.viewsCollection.findWhere({
                is_default: true
            });
        },

        /**
         * @private
         *
         * @returns {boolean}
         */
        _isCurrentViewSystem: function() {
            return this._getCurrentView().value === this.DEFAULT_GRID_VIEW_ID;
        },

        /**
         * @private
         *
         * @returns {undefined|GridViewModel}
         */
        _getDefaultSystemViewModel: function() {
            return this.viewsCollection.findWhere({
                name: this.DEFAULT_GRID_VIEW_ID
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
                return this.title ? $.trim(this.title) : __('Please select view');
            }

            return $.trim(currentView.label);
        },

        /**
         * @private
         *
         * @param {string|number} name
         * @returns {undefined|Object}
         */
        _getView: function(name) {
            return _.findWhere(this.getViewChoices(), {value: name});
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
                sorters: model.get('sorters'),
                columns: model.get('columns'),
                appearanceType: model.get('appearanceType'),
                appearanceData: model.get('appearanceData')
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
                sorters: this.collection.state.sorters,
                columns: this.collection.state.columns,
                appearanceType: this.collection.state.appearanceType,
                appearanceData: this.collection.state.appearanceData
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
            if (currentView.value === this.DEFAULT_GRID_VIEW_ID) {
                title = this.defaultPrefix;
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

            if (this.adjustDocumentTitle) {
                mediator.execute('adjustTitle', this._createTitle(), true);
            }
        },

        /**
         *  Get data from UI
         * @param container
         * @returns {{label: *, is_default: *}}
         */

        getInputData: function(container) {
            return {
                label: $('input[name=name]', container).val(),
                is_default: $('input[name=is_default]', container).is(':checked')
            };
        }
    });

    return GridViewsView;
});
