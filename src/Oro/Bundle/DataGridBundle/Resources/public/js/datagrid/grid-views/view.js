define(function(require) {
    'use strict';

    const template = require('tpl-loader!orodatagrid/templates/datagrid/grid-view.html');
    const titleTemplate = require('tpl-loader!orodatagrid/templates/datagrid/grid-view-label.html');
    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const errorHandler = require('oroui/js/error');
    const GridViewModel = require('./model');
    const ViewNameModal = require('./view-name-modal');
    const mediator = require('oroui/js/mediator');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');
    const routing = require('routing');
    const config = {
        hideSwitcherOnNoData: true,
        ...require('module-config').default(module.id)
    };

    /**
     * Datagrid views widget
     *
     * @export  orodatagrid/js/datagrid/grid-views/view
     * @class   orodatagrid.datagrid.GridViewsView
     * @extends BaseView
     */
    const GridViewsView = BaseView.extend({
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
        showErrorMessage: false,

        /** @property */
        adjustDocumentTitle: true,

        /**
         * @inheritdoc
         */
        constructor: function GridViewsView(options) {
            GridViewsView.__super__.constructor.call(this, options);
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

            _.extend(this, _.pick(options, ['viewsCollection', 'title', 'appearances', 'uniqueId']));

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
         * @inheritdoc
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
            const value = $(e.currentTarget).data('value');
            this.changeView(value);
            this._updateTitle();

            this.prevState = this._getCurrentState();
            this.viewDirty = !this._isCurrentStateSynchronized();
        },

        /**
         * @param {Event} e
         */
        onSave: function(e) {
            const model = this._getEditableViewModel(e.currentTarget);

            this._onSaveModel(model);
        },

        _onSaveModel: function(model) {
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
                errorHandlerMessage: this.showErrorMessage,
                success: () => {
                    this._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            });
        },

        onSaveAs: function() {
            if (_.isObject(this.modal)) {
                this.modal.dispose();
            }

            const modal = new ViewNameModal();

            modal.on('ok', () => {
                const data = this.getInputData(modal.$el);
                const model = this._createBaseViewModel(data);

                if (model.isValid()) {
                    this.lockModelOnOkCloses(modal, true);
                    this._onSaveAsModel(model);
                } else {
                    this.lockModelOnOkCloses(modal, false);
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
            model.save(null, {
                wait: true,
                success: model => {
                    const currentModel = this._getCurrentDefaultViewModel();
                    const icon = this._getAppearanceIcon(model.get('appearanceType'));

                    model.set('name', model.get('id'));
                    model.set('icon', icon);
                    model.unset('id');
                    if (model.get('is_default') && currentModel) {
                        currentModel.set({is_default: false});
                    }
                    this.viewsCollection.add(model);
                    this.changeView(model.get('name'));
                    this.collection.state.gridView = model.get('name');
                    this.viewDirty = !this._isCurrentStateSynchronized();
                    this._updateTitle();
                    this._showFlashMessage('success', __('oro.datagrid.gridView.created'));
                    mediator.trigger('datagrid:' + this.gridName + ':views:add', model);
                },
                errorHandlerMessage: this.showErrorMessage,
                error: (model, response, options) => {
                    this.onError(model, response, options);
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
            const model = this._getEditableViewModel(e.currentTarget);

            model.save({
                label: model.get('label'),
                type: 'public'
            }, {
                wait: true,
                success: () => {
                    this._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            });
        },

        /**
         * @param {Event} e
         */
        onUnshare: function(e) {
            const model = this._getEditableViewModel(e.currentTarget);

            model.save({
                label: model.get('label'),
                type: 'private'
            }, {
                wait: true,
                success: () => {
                    this._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                }
            });
        },

        /**
         * @param {Event} e
         */
        onDelete: function(e) {
            const model = this._getModelForDelete(e.currentTarget);

            const confirm = new this.DeleteConfirmation(this.defaults.DeleteConfirmationOptions);
            confirm.on('ok', () => {
                model.destroy({wait: true});
                model.once('sync', function() {
                    this._showFlashMessage('success', __('oro.datagrid.gridView.deleted'));
                    mediator.trigger('datagrid:' + this.gridName + ':views:remove', model);
                }, this);
            });

            confirm.open();

            return confirm;
        },

        /**
         * @param {HTML} element
         */
        _getModelForDelete: function(element) {
            // Accepts a element, that is can used for users extends
            const id = this._getCurrentView().value;

            return this.viewsCollection.get(id);
        },

        /**
         * @param {Event} e
         */
        onRename: function(e) {
            if (_.isObject(this.modal)) {
                this.modal.dispose();
            }

            const model = this._getEditableViewModel(e.currentTarget);
            const modal = new ViewNameModal({
                defaultValue: model.get('label'),
                defaultChecked: model.get('is_default')
            });

            modal.on('ok', () => {
                const data = this.getInputData(modal.$el);

                model.set(data, {silent: true});

                if (model.isValid()) {
                    this.lockModelOnOkCloses(modal, true);
                    this._onRenameSaveModel(model);
                } else {
                    this.lockModelOnOkCloses(modal, false);
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
            model.save(
                null, {
                    wait: true,
                    success: savedModel => {
                        const currentDefaultViewModel = this._getCurrentDefaultViewModel();
                        const isCurrentDefault = currentDefaultViewModel === model;
                        const isCurrentWasDefault = currentDefaultViewModel === undefined;

                        if (model.get('is_default') && !isCurrentDefault) {
                            // if current view hadn't default property and it is going to be
                            currentDefaultViewModel.set({is_default: false});
                        } else if (isCurrentWasDefault) {
                            // if current view had 'default' property and this property was removed, there are no
                            // views with 'default' property and it shall be set to system view.
                            this._getDefaultSystemViewModel().set({is_default: true});
                        }

                        model.set({
                            label: savedModel.get('label')
                        });

                        this._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
                    },
                    errorHandlerMessage: this.showErrorMessage,
                    error: (model, response, options) => {
                        model.set('label', model.previous('label'));
                        this.onError(model, response, options);
                    }
                });
        },

        onError: function(model, response, options) {
            if (response.status === 400) {
                if (_.isObject(this.modal)) {
                    this.modal.open();
                }
                this._showNameError(this.modal, response);
            } else {
                errorHandler.showErrorInUI(response);
            }
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
            const showIcons = _.uniq(this.viewsCollection.pluck('icon')).length > 1;
            const choices = this.viewsCollection.map(function(model, iteratee) {
                return {
                    label: model.getLabel(),
                    icon: showIcons ? model.get('icon') : false,
                    value: model.get('name')
                };
            }, this);

            const defaultItem = _.findWhere(choices, {value: this.DEFAULT_GRID_VIEW_ID});
            if (defaultItem.label === this.DEFAULT_GRID_VIEW_ID) {
                defaultItem.label = this.defaultPrefix + (this.title || '');
            }

            return choices;
        },

        /**
         * @param {Event} e
         */
        onUseAsDefault: function(e) {
            let isDefault = 1;
            const defaultModel = this._getCurrentDefaultViewModel();
            const gridName = this.gridName;
            const currentViewModel = this._getEditableViewModel(e.currentTarget);
            let id = currentViewModel.id;
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
                response => {
                    if (defaultModel) {
                        defaultModel.set({is_default: false});
                    }
                    currentViewModel.set({is_default: true});
                    this._showFlashMessage('success', __('oro.datagrid.gridView.updated'));
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
            this.render();
            this.collection.state.gridView = this.DEFAULT_GRID_VIEW_ID;

            const systemModel = this._getDefaultSystemViewModel();
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
            let viewState;
            const view = this.viewsCollection.get(gridView);

            if (view) {
                viewState = _.extend({}, this.collection.initialState, view.toGridState());
                this.collection.updateState(viewState);
                this.collection.fetch({reset: true});
            }

            return this;
        },

        render: function() {
            let content;
            const isCollectionEmpty = this.collection.length === 0 && _.isEmpty(this.collection.state.filters);

            if (config.hideSwitcherOnNoData && isCollectionEmpty) {
                content = this.renderPlainTitle();
            } else {
                this._checkCurrentState();

                const title = this.renderTitle();
                const actions = this._getViewActions();

                content = this.template({
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
            }

            this.$el.html(content);

            mediator.trigger('layout:reposition');

            return this;
        },

        /**
         * @returns {string}
         */
        renderTitle: function() {
            return this.titleTemplate({
                uniqueId: this.uniqueId,
                title: this._getCurrentViewLabel(),
                hasCaret: true,
                navbar: Boolean(this.title)
            });
        },

        /**
         * @returns {string}
         */
        renderPlainTitle: function() {
            return this.titleTemplate({
                uniqueId: this.uniqueId,
                title: this.title || '',
                hasCaret: false,
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
            const currentGridView = this._getCurrentViewModel();

            return this._getActions(currentGridView);
        },

        /**
         * @param GridView
         * @returns {*[]}
         * @private
         */
        _getActions: function(GridView) {
            const currentDefaultView = this._getCurrentDefaultViewModel();

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
            const currentView = this._getCurrentView();

            if (_.isUndefined(currentView)) {
                return;
            }

            return this.viewsCollection.findWhere({
                name: currentView.value
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
            const currentView = this._getCurrentView();

            return currentView && currentView.value === this.DEFAULT_GRID_VIEW_ID;
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
            const currentView = this._getCurrentView();

            if (typeof currentView === 'undefined') {
                return this.title ? this.title.trim() : __('Please select view');
            }

            return currentView.label.trim();
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
            const modelState = this._getCurrentViewModelState();
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
            const model = this._getCurrentViewModel();
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
            const currentView = this._getCurrentView();
            if (!currentView) {
                return this.originalTitle;
            }

            let title = currentView.label;
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
            let opts = options || {};
            const id = this.$el.closest('.ui-widget-content').attr('id');

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
            const responseJSON = response.responseJSON;
            const errors = responseJSON.errors ? responseJSON.errors.children.label.errors : null;
            const message = responseJSON.message;
            const err = errors ? errors[0] : message;

            if (err) {
                modal.setNameError(err);
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
