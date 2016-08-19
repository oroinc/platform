define(function(require) {
    'use strict';

    /**
     * This plugin changes grid appearance to scrum board
     *
     * @param {Object}   main - instance of datagrid component
     * @param {Object}   options - options container
     * @param {Function} options.pagesize - pagesize to use, 10 by default
     * @param {Function} options.board_view - board view
     * @param {Function} options.column_view - column view
     * @param {Function} options.column_header_view - column header view
     * @param {Function} options.card_view - card view
     * @param {Object}   options.transition - transition configuration
     * @param {Function} options.transition.class - transition class implementation
     * @param {Function} options.transition.save_api_accessor - api accessor to use when saving transition
     */
    var BoardAppearancePlugin;
    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var collectionTools = require('oroui/js/tools/collection-tools');
    var tools = require('oroui/js/tools');
    var BoardDataCollection = require('../../models/board-data-collection');

    BoardAppearancePlugin = BasePlugin.extend({
        /**
         * Default pagesize
         * @type {Number}
         */
        defaultPagesize: 10,

        /**
         * @inheritDoc
         */
        enable: function() {
            var boardPlugin = this;

            var BoardView = this.options.board_view;
            var ColumnView = this.options.column_view;
            var ColumnHeaderView = this.options.column_header_view;
            var Card = this.options.card_view;

            this.view = new BoardView({
                readonly: this.options.readonly,
                columnView: ColumnView,
                columnHeaderView: ColumnHeaderView,
                cardView: Card,
                boardPlugin: this,
                columns: this.getColumns(),
                boardCollection: this.getBoardCollection(),
                serverCollection: this.main.collection,
                cardActions: this.main.grid.rowActions
            });

            this.listenTo(this.view, 'update', function(model, updateOptions) {
                var transition = updateOptions.column.get('transition').class.build(
                    model,
                    updateOptions.column,
                    this,
                    updateOptions.relativePosition
                );
                transition.start();
            });

            this.listenTo(this.view, 'navigate', function navigate(model, columnDefinition, options) {
                if (!options.parameters) {
                    options.parameters = {};
                }
                options.parameters.boardColumnIds = JSON.stringify(columnDefinition.ids);
                this.main.grid.runRowClickAction(model, options);
            });

            this.listenTo(this.view, 'loadMoreIfPossible', function() {
                if (this.main.collection.isLoadingMore) {
                    return;
                }
                if (!this.main.collection.hasExtraRecordsToLoad()) {
                    return;
                }
                this.main.collection.loadMore();
            });
            this.view.render();
            this.view.$el.insertAfter(this.main.$el.find('.other-scroll-container'));
            this.main.$el.find('.other-scroll-container, .pagination, .page-size, ' +
                '.column-manager, .extra-actions-panel').hide();
            this.main.$el.find('.visible-items-counter').show();
            this.main.$el.find('.board').show();

            // disable sorting by group_by property
            this.restoreSortableOnColumns = [];
            this.main.grid.columns.filter(function(column) {
                if (column.get('name') === boardPlugin.options.group_by) {
                    return true;
                }
            }).forEach(function(column) {
                if (column.get('sortable')) {
                    boardPlugin.restoreSortableOnColumns.push(column);
                    column.set({
                        sortable: false
                    });
                }
            });

            if (this.main.collection.state.appearanceType !== 'board' ||
                this.main.collection.state.appearanceData.id !== this.options.id) {
                // update raw values in the collection state
                // all updates will be used on collection reloading during setAppearance call

                // set pagesize
                this.oldPageSize = this.main.collection.state.pageSize;
                this.main.collection.state.pageSize = this.options.pagesize || this.defaultPagesize;

                // update sorting if previously selected value was grouping column
                if (this.main.collection.state.sorters.hasOwnProperty(this.options.group_by)) {
                    this.oldSorting = _.extend({}, this.main.collection.state.sorters);
                    delete this.main.collection.state.sorters[this.options.group_by];
                    if (_.keys(this.main.collection.state.sorters).length === 0) {
                        // use sorting from initialState
                        this.main.collection.state.sorters = _.extend({}, this.main.collection.initialState.sorters);
                    }
                }
                this.main.collection.setAppearance('board', {id: this.options.id});
            }

            BoardAppearancePlugin.__super__.enable.call(this);
        },

        /**
         * @inheritDoc
         */
        disable: function() {
            this.main.$el.find('.board').hide();
            this.main.$el.find('.other-scroll-container, .pagination, .page-size, ' +
                '.column-manager, .extra-actions-panel').show();
            this.main.$el.find('.visible-items-counter').hide();

            // restore sorting settings
            if (this.restoreSortableOnColumns && this.restoreSortableOnColumns.length) {
                this.restoreSortableOnColumns.forEach(function(column) {
                    column.set({
                        sortable: true
                    });
                });
                delete this.restoreSortableOnColumns;
            }

            // restore collection options
            if (!this.main.pluginManager.disposing) {
                if (this.main.collection.state.appearanceType !== 'grid' ||
                    this.main.collection.state.appearanceData.id !== void 0) {
                    // update raw values in the collection state
                    // all updates will be used on collection reloading during setAppearance call

                    // restore sorting
                    if (this.oldSorting) {
                        this.main.collection.state.sorters = this.oldSorting ||
                            this.main.collection.initialState.sorters;
                        delete this.oldSorting;
                    }

                    // restore pagesize, will be used during setAppearance call
                    this.main.collection.state.pageSize = this.oldPageSize ||
                        this.main.collection.initialState.pageSize;
                    this.main.collection.setAppearance('grid', {id: void 0});
                }
            }
            if (this._collection) {
                this._collection.dispose();
            }
            this.view.dispose();
            if (!this._columns) {
                this._columns.each(function(innerCollection) {
                    innerCollection.dispose();
                });
                this._columns.dispose();
            }
            BoardAppearancePlugin.__super__.disable.call(this);
        },

        /**
         * Returns collection in the format applicable for board view
         * @return {BoardDataCollection}
         */
        getBoardCollection: function() {
            if (!this._collection) {
                this._collection = new BoardDataCollection(this.main.collection.models);
                this._collection.listenTo(this.main.collection, 'add remove reset sort', _.bind(function() {
                    this._collection.reset(this.main.collection.models);
                }, this));
            }
            return this._collection;
        },

        /**
         * Returns configured collection of board columns
         *
         * @return {Backbone.Collection}
         */
        getColumns: function() {
            var component = this;
            function createBoardColumnDefinition(id, column) {
                return {
                    id: id,
                    ids: column.ids,
                    columnDefinition: column,
                    label: column.label,
                    items: collectionTools.createFilteredCollection(component.getBoardCollection(), {
                        criteria: function(item) {
                            return column.ids.indexOf(item.get(component.options.group_by)) !== -1;
                        }
                    }),
                    transition: column.transition
                };
            }
            if (!this._columns) {
                var columnCollections = [];
                for (var i = 0; i < this.options.columns.length; i++) {
                    var column = this.options.columns[i];
                    columnCollections.push(createBoardColumnDefinition(i, column));
                }
                this._columns = new Backbone.Collection(columnCollections);
            }
            return this._columns;
        }
    }, {
        /**
         * default views
         */
        defaultOptions: {
            board_view: 'orodatagrid/js/app/views/board/board-view',
            card_view: 'orodatagrid/js/app/views/board/card-view',
            column_header_view: 'orodatagrid/js/app/views/board/column-header-view',
            column_view: 'orodatagrid/js/app/views/board/column-view'
        },

        /**
         * default save api accessor configuration
         */
        saveApiAccessorDefaults: {
            'class': 'oroui/js/tools/api-accessor',
            http_method: 'PATCH'
        },

        /**
         * default transition options
         */
        transitionDefaults: {
            'class': 'orodatagrid/js/app/transitions/update-main-property-transition'
        },

        /**
         * Processes options during building (see orodatagrid/js/appearance/builder.js). Preloades views,
         * transition class and save_api_accessor class.
         *
         * @param {Object} options - current board options
         * @param {Object} gridConfiguration - grid configuration
         * @return {$.Deferred}
         */
        processMetadata: function(options, gridConfiguration) {
            _.defaults(options, this.defaultOptions);

            if (!options.save_api_accessor) {
                options.save_api_accessor = _.extend({}, gridConfiguration.metadata.inline_editing.save_api_accessor);
            }

            _.defaults(options.save_api_accessor, this.saveApiAccessorDefaults);

            // prepare transition options
            options.columns.forEach(function(column) {
                if (!column.transition) {
                    column.transition = options.default_transition;
                }
                if (!column.transition.save_api_accessor) {
                    column.transition.save_api_accessor = options.save_api_accessor;
                }
                _.extend(column.transition, BoardAppearancePlugin.transitionDefaults);
            });

            return $.when.apply($, [
                tools.loadModuleAndReplace(options, 'board_view'),
                tools.loadModuleAndReplace(options, 'card_view'),
                tools.loadModuleAndReplace(options, 'column_header_view'),
                tools.loadModuleAndReplace(options, 'column_view'),
                $.when.apply($, options.columns.map(function(column) {
                    return tools.loadModuleAndReplace(column.transition, 'class');
                })),
                $.when.apply($, options.columns.map(function(column) {
                    return tools.loadModuleAndReplace(column.transition.save_api_accessor, 'class');
                }))
            ]);
        }
    });

    return BoardAppearancePlugin;
});
