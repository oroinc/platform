/*jslint nomen:true*/
/*global define*/
define([
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    './collection',
    './model',
    'oroui/js/modal',
    'oroui/js/mediator'
], function (Backbone, _, __, GridViewsCollection, GridViewModel, Modal, mediator) {
    'use strict';
    var $, GridViewsView;
    $ = Backbone.$;

    /**
     * Datagrid views widget
     *
     * @export  orodatagrid/js/datagrid/grid-views/view
     * @class   orodatagrid.datagrid.GridViewsView
     * @extends Backbone.View
     */
    GridViewsView = Backbone.View.extend({
        className: 'grid-views pull-left',

        /** @property */
        events: {
            "click .views-group a": "onChange",
            "click a.save": "onSave",
            "click a.save_as": "onSaveAs",
            "click a.share": "onShare",
            "click a.unshare": "onUnshare",
            "click a.delete": "onDelete"
        },

        /** @property */
        template: _.template(
            '<% if (showEditedLabel) { %>' +
                '<div class="edited-label"><%= editedLabel %></div>' +
            '<% } %>' +
            '<div class="btn-toolbar">' +
                '<% if (choices.length) { %>' +
                    '<div class="btn-group views-group">' +
                        '<button data-toggle="dropdown" class="btn dropdown-toggle <% if (disabled) { %>disabled<% } %>">' +
                            '<%=  current %>' + '<span class="caret"></span>' +
                        '</button>' +
                        '<ul class="dropdown-menu pull-right">' +
                            '<% _.each(choices, function (choice) { %>' +
                                '<li><a href="#" data-value="' + '<%= choice.value %>' + '">' + '<%= choice.label %>' + '</a></li>' +
                            '<% }); %>' +
                        '</ul>' +
                    '</div>' +
                '<% } %>' +
                '<% if (showActions) { %>' +
                    '<div class="btn-group actions-group">' +
                        '<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">' +
                            '<%= actionsLabel %><span class="caret">' +
                        '</a>' +
                        '<ul class="dropdown-menu">' +
                            '<% _.each(actions, function(action) { %>' +
                                '<% if (action.enabled) { %>' +
                                    '<li><a href="#" class="<%= action.name %>"><%= action.label %></a></li>' +
                                '<% } %>' +
                            '<% }); %>' +
                        '</ul>' +
                    '</div>' +
                '<% } %>' +
            '</div>'
        ),

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
        viewsCollection: GridViewsCollection,

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Boolean} [options.enable]
         * @param {Array}   [options.choices]
         * @param {Array}   [options.views]
         */
        initialize: function (options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError("'collection' is required");
            }

            if (options.choices) {
                this.choices = _.union(this.choices, options.choices);
            }

            if (options.permissions) {
                this.permissions = _.extend(this.permissions, options.permissions);
            }

            this.collection = options.collection;
            this.enabled = options.enable != false;

            this.listenTo(this.collection, "updateState", this.render);
            this.listenTo(this.collection, "beforeFetch", this.render);
            this.listenTo(this.collection, "reset", this._onCollectionReset);
            this.listenTo(this.collection, "reset", this.render);

            options.views = options.views || [];
            _.each(options.views, function(view) {
                view.grid_name = options.collection.inputName;
            });

            this.viewsCollection = new this.viewsCollection(options.views);

            var currentState = this._getCurrentState();
            var modelState = this._getCurrentViewModelState();
            if (modelState && !_.isEqual(currentState, modelState)) {
                this.viewDirty = true;
            }
            this.prevState = currentState;

            this.listenTo(this.viewsCollection, 'add', this._onModelAdd);
            this.listenTo(this.viewsCollection, 'remove', this._onModelRemove);

            GridViewsView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.viewsCollection.dispose();
            delete this.viewsCollection;
            GridViewsView.__super__.dispose.call(this);
        },

        /**
         * Disable view selector
         *
         * @return {*}
         */
        disable: function () {
            this.enabled = false;
            this.render();

            return this;
        },

        /**
         * Enable view selector
         *
         * @return {*}
         */
        enable: function () {
            this.enabled = true;
            this.render();

            return this;
        },

        /**
         * Select change event handler
         *
         * @param {Event} e
         */
        onChange: function (e) {
            e.preventDefault();
            var value = $(e.target).data('value');
            if (value !== this.collection.state.gridView) {
                this.changeView(value);
            }

            this.prevState = this._getCurrentState();
            this.viewDirty = false;
        },

        /**
         * @param {Event} e
         */
        onSave: function(e) {
            var model = this._getCurrentViewModel();

            model.save({
                label: this._getCurrentViewLabel(),
                filters: this.collection.state.filters,
                sorters: this.collection.state.sorters
            }, {
                wait: true
            });

            model.once('sync', function() {
                mediator.execute('showFlashMessage', 'success', __('oro.datagrid.gridView.updated'));
            });
        },

        /**
         * @param {Event} e
         */
        onSaveAs: function(e) {
            var modal = new Modal({
                title: 'Filter configuration',
                content: '<div class="form-horizontal">' +
                            '<div class="control-group">' +
                                '<label class="control-label" for="gridViewName">' + __('oro.datagrid.gridView.name') + ':</label>' +
                                '<div class="controls">' +
                                    '<input id="gridViewName" name="name" type="text">' +
                                '</div>' +
                            '</div>' +
                         '</div>'
            });

            var self = this;
            modal.on('ok', function(e) {
                var model = new GridViewModel({
                    label: this.$('input[name=name]').val(),
                    type: 'private',
                    grid_name: self.collection.inputName,
                    filters: self.collection.state.filters,
                    sorters: self.collection.state.sorters
                });
                model.save(null, {
                    wait: true
                });
                model.once('sync', function(model) {
                    this.viewsCollection.add(model);
                    this.changeView(model.get('name'));
                }, self);
            });

            modal.open();
        },

        /**
         * @param {Event} e
         */
        onShare: function(e) {
            var model = this._getCurrentViewModel();

            model.save({
                label: this._getCurrentViewLabel(),
                type: 'public'
            }, {
                wait: true
            });

            model.once('sync', function() {
                this.render();
                mediator.execute('showFlashMessage', 'success', __('oro.datagrid.gridView.updated'));
            }, this);
        },

        /**
         * @param {Event} e
         */
        onUnshare: function(e) {
            var model = this._getCurrentViewModel();

            model.save({
                label: this._getCurrentViewLabel(),
                type: 'private'
            }, {
                wait: true
            });

            model.once('sync', function() {
                this.render();
                mediator.execute('showFlashMessage', 'success', __('oro.datagrid.gridView.updated'));
            }, this);
        },

        /**
         * @param {Event} e
         */
        onDelete: function(e) {
            var model = this._getCurrentViewModel();

            model.destroy({wait: true});
        },

        /**
         * @private
         *
         * @param {GridViewModel} model
         */
        _onModelAdd: function(model) {
            model.set('name', model.get('id'));
            model.unset('id');

            this.choices.push({
                label: model.get('label'),
                value: model.get('name')
            });
            this.collection.state.gridView = model.get('name');
            this.render();

            mediator.execute('showFlashMessage', 'success', __('oro.datagrid.gridView.created'));
        },

        /**
         * @private
         *
         * @param {GridViewModel} model
         */
        _onModelRemove: function(model) {
            this.choices = _.reject(this.choices, function (item) {
                return item.value == this.collection.state.gridView;
            }, this);
            this.collection.state.gridView = null;

            this.render();

            mediator.execute('showFlashMessage', 'success', __('oro.datagrid.gridView.deleted'));
        },

        /**
         * @private
         */
        _onCollectionReset: function() {
            var newState = this._getCurrentState();
            if (_.isEqual(newState, this.prevState)) {
                return;
            }

            this.viewDirty = true;
            this.prevState = newState;
        },

        /**
         * Updates collection
         *
         * @param gridView
         * @returns {*}
         */
        changeView: function (gridView) {
            var view, viewState;
            view = this.viewsCollection.get(gridView);

            if (view) {
                viewState = _.extend({}, this.collection.initialState, view.toGridState());
                this.collection.updateState(viewState);
                this.collection.fetch({reset: true});
            }

            return this;
        },

        render: function (o) {
            var html;
            this.$el.empty();

            var actions = this._getCurrentActions();

            html = this.template({
                disabled: !this.enabled,
                choices: this.choices,
                current: this._getCurrentViewLabel(),
                actionsLabel: __('oro.datagrid.gridView.actions'),
                editedLabel: __('oro.datagrid.gridView.data_edited'),
                showEditedLabel: this.viewDirty,
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
                    enabled: typeof currentView !== 'undefined' && this.permissions.EDIT &&
                             (currentView.get('type') === 'private' ||
                                (currentView.get('type') === 'public' && this.permissions.EDIT_SHARED))
                },
                {
                    label: __('oro.datagrid.action.save_grid_view_as'),
                    name: 'save_as',
                    enabled: this.permissions.CREATE
                },
                {
                    label: __('oro.datagrid.action.share_grid_view'),
                    name: 'share',
                    enabled: typeof currentView !== 'undefined' &&
                            currentView.get('type') === 'private' && this.permissions.SHARE
                },
                {
                    label: __('oro.datagrid.action.unshare_grid_view'),
                    name: 'unshare',
                    enabled: typeof currentView !== 'undefined' &&
                            currentView.get('type') === 'public' && this.permissions.EDIT_SHARED
                },
                {
                    label: __('oro.datagrid.action.delete_grid_view'),
                    name: 'delete',
                    enabled: typeof currentView !== 'undefined' && currentView.get('type') !== 'system' &&
                             this.permissions.DELETE
                }
            ];
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

            return typeof currentView === 'undefined' ? __('Please select view') : currentView.label;
        },

        /**
         * @private
         *
         * @returns {undefined|Object}
         */
        _getCurrentView: function() {
            var currentViews =  _.filter(this.choices, function (item) {
                return item.value == this.collection.state.gridView;
            }, this);

            return _.first(currentViews);
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
        }
    });

    return GridViewsView;
});
