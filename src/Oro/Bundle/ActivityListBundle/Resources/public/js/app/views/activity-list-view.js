define(function(require) {
    'use strict';

    var ActivityListView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var DialogWidget = require('oro/dialog-widget');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    ActivityListView = BaseCollectionView.extend({
        options: {
            configuration: {},
            template: null,
            itemTemplate: null,
            itemViewIdPrefix: 'activity-',
            listSelector: '.items.list-box',
            fallbackSelector: '.no-data',
            loadingSelector: '.loading-mask',
            listWidgetSelector: '.activities-container .activity-list-widget',
            activityListSelector: '.activity-list',
            collection: null,
            urls: {
                viewItem: null,
                updateItem: null,
                deleteItem: null
            },
            messages: {},
            ignoreHead: false,
            doNotFetch: false,
            reloadOnAdd: true,
            reloadOnUpdate: true,
            triggerRefreshEvent: true
        },

        listen: {
            'toView collection': '_viewItem',
            'toViewGroup collection': '_viewGroup',
            'toEdit collection': '_editItem',
            'toDelete collection': '_deleteItem'
        },

        EDIT_DIALOG_CONFIGURATION_DEFAULTS: {
            regionEnabled: false,
            incrementalPosition: false,
            alias: 'activity_list:item:update',
            dialogOptions: {
                modal: true,
                resizable: true,
                width: 515,
                autoResize: true
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function ActivityListView() {
            ActivityListView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            _.defaults(this.options.messages, {
                editDialogTitle: __('oro.activitylist.edit_title'),
                itemSaved: __('oro.activitylist.item_saved'),
                itemRemoved: __('oro.activitylist.item_removed'),

                deleteConfirmation: __('oro.activitylist.delete_confirmation'),
                deleteItemError: __('oro.activitylist.delete_error'),

                loadItemsError: __('oro.activitylist.load_error'),
                forbiddenError: __('oro.activitylist.forbidden_error'),
                forbiddenActivityDataError: __('oro.activitylist.forbidden_activity_data_view_error')
            });

            this.template = _.template($(this.options.template).html());
            this.isFiltersEmpty = true;
            this.gridToolbar = $(
                this.options.listWidgetSelector + ' ' + this.options.activityListSelector + ' .grid-toolbar'
            );

            if (this.options.reloadOnAdd) {
                /**
                 * on adding activity item listen to "widget:doRefresh:activity-list-widget"
                 */
                mediator.on('widget:doRefresh:activity-list-widget', this._reloadOnAdd, this);
            }

            if (this.options.reloadOnUpdate) {
                /**
                 * on editing activity item listen to "widget_success:activity_list:item:update"
                 */
                mediator.on('widget_success:activity_list:item:update', this._reload, this);
            }

            ActivityListView.__super__.initialize.call(this, options);

            if (!this.doNotFetch) {
                this._initPager();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.itemEditDialog;

            mediator.off('widget:doRefresh:activity-list-widget', this._reloadOnAdd, this);
            mediator.off('widget_success:activity_list:item:update', this._reload, this);

            ActivityListView.__super__.dispose.call(this);
        },

        initItemView: function(model) {
            var className = model.getRelatedActivityClass();
            var configuration = this.options.configuration[className];
            if (this.itemView) {
                return new this.itemView({
                    autoRender: false,
                    model: model,
                    configuration: configuration,
                    ignoreHead: this.options.ignoreHead
                });
            } else {
                ActivityListView.__super__.render.apply(this, arguments);
            }
        },

        refresh: function() {
            this.collection.setPage(1);
            this.collection.resetPageFilter();

            this._reload();

            if (this.options.triggerRefreshEvent) {
                mediator.trigger('widget_success:activity_list:refresh');
            }
        },

        _initPager: function() {
            if (this.collection.getCount() && this.collection.getPage() === 1) {
                this._toggleNext(true);
            }

            if (this.collection.getPage() === 1) {
                this._togglePrevious();
            } else {
                this._togglePrevious(true);
            }

            if (this.collection.getCount() < this.collection.getPageSize()) {
                this._toggleNext();
            }

            if (this.collection.getCount() === 0 &&
                this.isFiltersEmpty &&
                this.collection.getPage() === 1 &&
                !this.collection.models.length
            ) {
                this.gridToolbar.hide();
            } else {
                this.gridToolbar.show();
            }
        },

        /**
         * Fetches loading container element
         *
         *  - returns loading container passed over options,
         *    or the view element as default loading container
         *
         * @returns {HTMLElement|undefined}
         * @protected
         * @override
         */
        _getLoadingContainer: function() {
            var loadingContainer = this.options.loadingContainer;
            if (loadingContainer instanceof $) {
                // fetches loading container from options
                loadingContainer = loadingContainer.get(0);
            }
            if (!loadingContainer) {
                // uses the element as default loading container
                loadingContainer = this.$el.get(0);
            }
            return loadingContainer;
        },

        goto_previous: function() {
            var currentPage = this.collection.getPage();
            if (currentPage === 1) {
                return;
            }

            if (currentPage === 2) {
                this.collection.setPage(1);
                this.collection.resetPageFilter();

                this._reload();
            } else {
                var nextPage = currentPage - 1;
                this.collection.setPage(nextPage);

                this._setupPageFilterForPrevAction();

                this._reload();
            }

            this._toggleNext(true);
        },
        goto_next: function() {
            if (this.collection.getCount() < this.collection.getPageSize()) {
                return;
            }
            var currentPage = this.collection.getPage();

            this.collection.setPage(currentPage + 1);
            this.collection.setPageTotal(this.collection.getPageTotal() + 1);

            this._setupPageFilterForNextAction();

            this._reload();
        },

        _setupPageFilterForPrevAction: function() {
            var model = this.collection.first();
            var sameModelIds = this._findSameModelsBySortingField(model);

            this.collection.setPageFilterDate(model.attributes[this.collection.pager.sortingField]);
            this.collection.setPageFilterIds(sameModelIds.length ? sameModelIds : [model.id]);
            this.collection.setPageFilterAction('prev');
        },
        _setupPageFilterForNextAction: function() {
            var model = this.collection.last();
            var sameModelIds = this._findSameModelsBySortingField(model);

            this.collection.setPageFilterDate(model.attributes[this.collection.pager.sortingField]);
            this.collection.setPageFilterIds(sameModelIds.length ? sameModelIds : [model.id]);
            this.collection.setPageFilterAction('next');
        },
        /**
         * Finds the same models in collection by sorting field
         * @param model ActivityModel to be used for comparison
         */
        _findSameModelsBySortingField: function(model) {
            var modelIds = [];
            var sortingField = this.collection.pager.sortingField;
            var sameModels = _.filter(this.collection.models, function(collectionModel) {
                return collectionModel.attributes[sortingField] === model.attributes[sortingField];
            }, this);
            if (sameModels.length) {
                modelIds = _.map(sameModels, function(collectionModel) {
                    return collectionModel.id;
                });
            }

            return modelIds;
        },

        _togglePrevious: function(enable) {
            $(this.options.listWidgetSelector + ' .pagination-previous').attr('disabled', enable === void 0);
        },

        _toggleNext: function(enable) {
            $(this.options.listWidgetSelector + ' .pagination-next').attr('disabled', enable === void 0);
        },

        _reloadOnAdd: function() {
            if (this.collection.getPage() === 1) {
                this.collection.resetPageFilter();
                this._reload();
            }
        },

        _reload: function() {
            var itemViews;
            // please note that _hideLoading will be called in renderAllItems() function
            this._showLoading();
            if (this.options.doNotFetch) {
                this._hideLoading();
                return;
            }
            try {
                // store views state
                this.oldViewStates = {};
                itemViews = this.getItemViews();
                this.oldViewStates = _.map(itemViews, function(view) {
                    return {
                        attrs: view.model.toJSON(),
                        collapsed: view.isCollapsed(),
                        height: view.$el.height()
                    };
                });

                this.collection.fetch({
                    reset: true,
                    success: _.bind(function() {
                        this._initPager();
                        this._hideLoading();
                    }, this),
                    error: _.bind(function(collection, response) {
                        this._showLoadItemsError(response.responseJSON || {});
                    }, this)
                });
            } catch (err) {
                this._showLoadItemsError(err);
            }
        },

        renderAllItems: function() {
            var result;
            var i;
            var view;
            var model;
            var oldViewState;
            var contentLoadedPromises;
            var deferredContentLoading;

            result = ActivityListView.__super__.renderAllItems.apply(this, arguments);

            contentLoadedPromises = [];

            if (this.oldViewStates) {
                // restore state
                for (i = 0; i < this.oldViewStates.length; i++) {
                    oldViewState = this.oldViewStates[i];
                    model = this.collection.findSameActivity(oldViewState.attrs);
                    if (model) {
                        view = this.getItemView(model);
                        model.set('is_loaded', false);
                        if (view && !oldViewState.collapsed) {
                            view.toggle();
                            view.getAccorditionBody().addClass('in');
                            view.getAccorditionToggle().removeClass('collapsed');
                            if (view.model.get('isContentLoading')) {
                                // if model is loading - need to wait until content will be loaded before _hideLoading()
                                // also preserve height during loading
                                view.$el.height(oldViewState.height);
                                deferredContentLoading = $.Deferred();
                                contentLoadedPromises.push(deferredContentLoading);
                                view.model.once(
                                    'change:isContentLoading',
                                    _.bind(function(view, deferredContentLoading) {
                                        // reset height
                                        view.$el.height('');
                                        deferredContentLoading.resolve();
                                    }, this, view, deferredContentLoading)
                                );
                            }
                        }
                    }
                }
                delete this.oldViewStates;
            }

            $.when.apply($, contentLoadedPromises).done(_.bind(function() {
                this._hideLoading();
            }, this));

            return result;
        },

        _viewItem: function(model) {
            this._loadModelContentHTML(model, 'itemView');
        },

        _viewGroup: function(model) {
            this._loadModelContentHTML(model, 'groupView');
        },

        _loadModelContentHTML: function(model, actionKey) {
            var url = this._getUrl(actionKey, model);
            if (model.get('is_loaded') === true) {
                return;
            }
            model.loadContentHTML(url)
                .fail(_.bind(function(response) {
                    if (response.status === 403) {
                        this._showForbiddenActivityDataError(response.responseJSON || {});
                    } else {
                        this._showLoadItemsError(response.responseJSON || {});
                    }
                }, this));
        },

        _editItem: function(model, extraOptions) {
            if (!this.itemEditDialog) {
                var unescapeHTML = function unescapeHTML(unsafe) {
                    return unsafe
                        .replace(/&nbsp;/g, ' ')
                        .replace(/&amp;/g, '&')
                        .replace(/&lt;/g, '<')
                        .replace(/&gt;/g, '>')
                        .replace(/&quot;/g, '\"')
                        .replace(/&#039;/g, '\'');
                };

                var dialogConfiguration = $.extend(true, {}, this.EDIT_DIALOG_CONFIGURATION_DEFAULTS, extraOptions, {
                    url: this._getUrl('itemEdit', model),
                    title: unescapeHTML(model.get('subject')),
                    dialogOptions: {
                        close: _.bind(function() {
                            delete this.itemEditDialog;
                        }, this)
                    }
                });
                this.itemEditDialog = new DialogWidget(dialogConfiguration);

                this.itemEditDialog.render();
            }
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
                // in case deleting the last item on page - will show the previous one
                if (this.collection.getCount() === 1) {
                    // the first page never has pageFilters
                    // in case 2nd page and last item deletion - just reset pageFilter, this will give the 1st page
                    // in all other cases simulate `Prev` action
                    if (this.collection.getPage() <= 2) {
                        this.collection.resetPageFilter();
                    } else {
                        this.collection.setPage(this.collection.getPage() - 1);
                        this._setupPageFilterForPrevAction();
                    }
                }

                model.destroy({
                    wait: true,
                    url: this._getUrl('itemDelete', model),
                    success: _.bind(function() {
                        mediator.execute('showFlashMessage', 'success', this._getMessage('itemRemoved'));
                        mediator.trigger('widget_success:activity_list:item:delete');

                        this._reload();
                    }, this),
                    error: _.bind(function(model, response) {
                        if (!_.isUndefined(response.status) && response.status === 403) {
                            this._showForbiddenError(response.responseJSON || {});
                        } else {
                            this._showDeleteItemError(response.responseJSON || {});
                        }
                        this._hideLoading();
                    }, this)
                });
            } catch (err) {
                this._showDeleteItemError(err);
                this._hideLoading();
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
            var routes = model.get('routes');

            return routing.generate(routes[actionKey], {
                entity: model.get('relatedActivityClass'),
                id: model.get('relatedActivityId')
            });
        },

        _getMessage: function(labelKey) {
            return this.options.messages[labelKey];
        },

        _showLoading: function() {
            this.subview('loading').show();
        },

        _hideLoading: function() {
            if (this.subview('loading')) {
                this.subview('loading').hide();
            }
        },

        _showLoadItemsError: function(err) {
            this._showError(this.options.messages.loadItemsError, err);
        },

        _showDeleteItemError: function(err) {
            this._showError(this.options.messages.deleteItemError, err);
        },

        _showForbiddenActivityDataError: function(err) {
            this._showError(this.options.messages.forbiddenActivityDataError, err);
        },

        _showForbiddenError: function(err) {
            this._showError(this.options.messages.forbiddenError, err);
        },

        _showError: function(message, err) {
            this._hideLoading();
            mediator.execute('showErrorMessage', message, err);
        }
    });

    return ActivityListView;
});
