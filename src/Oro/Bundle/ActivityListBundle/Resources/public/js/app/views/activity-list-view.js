/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ActivityListView,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        mediator = require('oroui/js/mediator'),
        DialogWidget = require('oro/dialog-widget'),
        DeleteConfirmation = require('oroui/js/delete-confirmation'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    ActivityListView = BaseCollectionView.extend({
        options: {
            configuration: {},
            template: null,
            itemTemplate: null,
            itemViewIdPrefix: 'activity-',
            listSelector: '.items.list-box',
            fallbackSelector: '.no-data',
            loadingSelector: '.loading-mask',
            collection: null,
            urls: {
                viewItem: null,
                updateItem: null,
                deleteItem: null
            },
            messages: {},
            ignoreHead: false,
            doNotFetch: false
        },
        listen: {
            'toView collection': '_viewItem',
            'toViewGroup collection': '_viewGroup',
            'toEdit collection': '_editItem',
            'toDelete collection': '_deleteItem'
        },

        initialize: function (options) {
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

            /**
             * on adding activity item listen to "widget:doRefresh:activity-list-widget"
             */
            mediator.on('widget:doRefresh:activity-list-widget', this._reload, this);

            /**
             * on editing activity item listen to "widget_success:activity_list:item:update"
             */
            mediator.on('widget_success:activity_list:item:update', this._reload, this);

            ActivityListView.__super__.initialize.call(this, options);

            if (!this.doNotFetch) {
                this._initPager();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }

            delete this.itemEditDialog;

            mediator.off('widget:doRefresh:activity-list-widget', this._reload, this );
            mediator.off('widget_success:activity_list:item:update', this._reload, this);

            ActivityListView.__super__.dispose.call(this);
        },

        initItemView: function (model) {
            var className = model.getRelatedActivityClass(),
                configuration = this.options.configuration[className];
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

        refresh: function () {
            this.collection.setPage(1);
            this._setPageNumber();
            this._reload();
        },

        _initPager: function () {
            if (this.collection.getPageSize() < this.collection.getCount()) {
                this._toggleNext(true);
            } else {
                this._toggleNext();
            }
            $('.activity-list-widget .pagination-total-num').html(this.collection.pager.total);
            $('.activity-list-widget .pagination-total-count').html(this.collection.getCount());
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
        _getLoadingContainer: function () {
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

        goto_previous: function () {
            var currentPage = this.collection.getPage();
            if (currentPage > 1) {
                var nextPage = currentPage - 1;
                this.collection.setPage(nextPage);
                if (nextPage == 1) {
                    this._togglePrevious();
                }

                if (this.collection.pager.total > 1) {
                    this._toggleNext(true);
                } else {
                    this._toggleNext();
                }

                this._setPageNumber(nextPage);
                this._reload();
            }
        },

        goto_page: function (e) {
            var that = this.list,
                currentPage = that.collection.getPage(),
                maxPage = that.collection.pager.total,
                nextPage = parseInt($(e.target).val());

            if (_.isNaN(nextPage) || nextPage <= 0 || nextPage > maxPage || nextPage == currentPage) {
                $(e.target).val(currentPage);
                return;
            }

            that._togglePrevious(true);
            that._toggleNext(true);

            if (nextPage == 1) {
                that._togglePrevious();
            }
            if (nextPage == maxPage) {
                that._toggleNext();
            }

            that.collection.setPage(nextPage);
            that._setPageNumber(nextPage);
            that._reload();
        },

        goto_next: function () {
            var currentPage = this.collection.getPage();
            if (currentPage < this.collection.pager.total) {
                var nextPage = currentPage + 1;
                this.collection.setPage(nextPage);
                if (nextPage == this.collection.pager.total) {
                    this._toggleNext();
                } else {
                    this._toggleNext(true);
                }
                this._togglePrevious(true);

                this._setPageNumber(nextPage);
                this._reload();
            }
        },

        _setPageNumber: function (pageNumber) {
            if (_.isUndefined(pageNumber)) {
                pageNumber = 1;
            }
            $('.activity-list-widget .pagination-current').val(pageNumber);
        },

        _togglePrevious: function (enable) {
            if (_.isUndefined(enable)) {
                $('.activity-list-widget .pagination-previous').addClass('disabled');
            } else {
                $('.activity-list-widget .pagination-previous').removeClass('disabled');
            }
        },

        _toggleNext: function (enable) {
            if (_.isUndefined(enable)) {
                $('.activity-list-widget .pagination-next').addClass('disabled');
            } else {
                $('.activity-list-widget .pagination-next').removeClass('disabled');
            }
        },

        _reload: function () {
            var itemViews, cid, view;
            this._showLoading();
            if (this.options.doNotFetch) {
                this._hideLoading();
                return;
            }
            try {
                // store views state
                this.oldViewStates = {};
                itemViews = this.getItemViews();
                this.oldViewStates = _.map(itemViews, function (view) {
                    return {
                        attrs: view.model.toJSON(),
                        collapsed: view.isCollapsed()
                    };
                });

                this.collection.fetch({
                    reset: true,
                    success: _.bind(this._initPager, this),
                    error: _.bind(function (collection, response) {
                        this._showLoadItemsError(response.responseJSON || {});
                    }, this)
                });
            } catch (err) {
                this._showLoadItemsError(err);
            }
        },

        renderAllItems: function () {
            var result, i, viewsToggleRequested, view, model, oldViewState;

            result = ActivityListView.__super__.renderAllItems.apply(this, arguments);

            viewsToggleRequested = false;

            if (this.oldViewStates) {
                // restore state
                for (i = 0; i < this.oldViewStates.length; i++) {
                    oldViewState = this.oldViewStates[i];
                    model = this.collection.findSameActivity(oldViewState.attrs);
                    if (model) {
                        view = this.getItemView(model);
                        if (view && !oldViewState.collapsed && view.isCollapsed()) {
                            view.toggle();
                            view.getAccorditionBody().addClass('in');
                            view.getAccorditionToggle().removeClass('collapsed');
                            viewsToggleRequested = true;
                        }
                    }
                }
                delete this.oldViewStates;
            }

            if (!viewsToggleRequested) {
                this._hideLoading();
            }

            return result;
        },

        _viewItem: function (model) {
            this._loadModelContentHTML(model, 'itemView');
        },

        _viewGroup: function (model) {
            this._loadModelContentHTML(model, 'groupView');
        },

        _loadModelContentHTML: function (model, actionKey) {
            var url = this._getUrl(actionKey, model);
            if (model.get('is_loaded') === true) {
                return;
            }
            model.loadContentHTML(url)
                .fail(_.bind(function (response) {
                    if (response.status === 403) {
                        this._showForbiddenActivityDataError(response.responseJSON || {});
                    } else {
                        this._showLoadItemsError(response.responseJSON || {});
                    }
                }, this));
        },

        _editItem: function (model) {
            if (!this.itemEditDialog) {
                var unescapeHTML = function unescapeHtml(unsafe) {
                    return unsafe
                        .replace(/&nbsp;/g, " ")
                        .replace(/&amp;/g, "&")
                        .replace(/&lt;/g, "<")
                        .replace(/&gt;/g, ">")
                        .replace(/&quot;/g, "\"")
                        .replace(/&#039;/g, "'");
                };

                this.itemEditDialog = new DialogWidget({
                    'url': this._getUrl('itemEdit', model),
                    'title': unescapeHTML(model.get('subject')),
                    'regionEnabled': false,
                    'incrementalPosition': false,
                    'alias': 'activity_list:item:update',
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
            }
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
                    url: this._getUrl('itemDelete', model),
                    success: _.bind(function () {
                        mediator.execute('showFlashMessage', 'success', this._getMessage('itemRemoved'));
                        this._reload();
                    }, this),
                    error: _.bind(function (model, response) {
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
        _getUrl: function (actionKey, model) {
            var className = model.getRelatedActivityClass();
            var route = this.options.configuration[className].routes[actionKey];
            return routing.generate(route, {'id': model.get('relatedActivityId')});
        },

        _getMessage: function (labelKey) {
            return this.options.messages[labelKey];
        },

        _showLoading: function () {
            this.subview('loading').show();
        },

        _hideLoading: function () {
            this.subview('loading').hide();
        },

        _showLoadItemsError: function (err) {
            this._showError(this.options.messages.loadItemsError, err);
        },

        _showDeleteItemError: function (err) {
            this._showError(this.options.messages.deleteItemError, err);
        },

        _showForbiddenActivityDataError: function (err) {
            this._showError(this.options.messages.forbiddenActivityDataError, err);
        },

        _showForbiddenError: function (err) {
            this._showError(this.options.messages.forbiddenError, err);
        },

        _showError: function (message, err) {
            this._hideLoading();
            mediator.execute('showErrorMessage', message, err);
        }
    });

    return ActivityListView;
});
