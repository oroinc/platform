define(function(require) {
    'use strict';

    /**
     * Displays board. Supports:
     * - automatic loading more when user scrolls down
     * - sets view height so it doesn't break UI (like fullscreen plugin for a grid)
     * - supports no-data block
     *
     * @param {Object}   options - options container
     * @param {Function} options.boardView - board view
     * @param {Function} options.columnView - column view
     * @param {Function} options.columnHeaderView - column header view
     * @param {Function} options.cardView - card view
     * @param {Boolean}  options.readonly - true if d'n'd user interactions should be supported
     * @param {Backbone.Collection}   options.columns - collection of columns to display
     * @param {BoardAppearancePlugin} options.boardPlugin - plugin instance
     * @param {BoardDataCollection} options.boardCollection - collection for board view
     * @param {PageableCollection} options.serverCollection - base collection with data
     */
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const scrollHelper = require('oroui/js/tools/scroll-helper');

    const BoardView = BaseView.extend({
        /**
         * @inheritdoc
         */
        className: 'board',

        /**
         * @inheritdoc
         */
        template: require('tpl-loader!../../../../templates/board/board-view.html'),

        /**
         * Shared between shild views timeout to detect early status change
         */
        earlyChangeTimeout: 2000,

        /**
         * @inheritdoc
         */
        constructor: function BoardView(options) {
            BoardView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.readonly = options.readonly;
            this.columns = options.columns;
            this.boardPlugin = options.boardPlugin;
            this.columnView = options.columnView;
            this.columnHeaderView = options.columnHeaderView;
            this.cardView = options.cardView;
            this.boardCollection = options.boardCollection;
            this.serverCollection = options.serverCollection;
            this.cardActions = options.cardActions;
            this.listenTo(this.serverCollection, 'change reset add remove', this.updateNoDataBlock);
            BoardView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            clearInterval(this.setTrackScrollInterval);
            BoardView.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        _ensureElement: function() {
            BoardView.__super__._ensureElement.call(this);
            if (this.className) {
                this.$el.addClass(_.result(this, 'className'));
            }
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const board = this;
            BoardView.__super__.render.call(this);
            this.updateNoDataBlock();
            this.subview('header', new BaseCollectionView({
                autoRender: true,
                el: this.$('.board-header'),
                collection: this.columns,
                itemView: function(options) {
                    options = _.extend(_.pick(board, 'boardCollection'), options);
                    return new board.columnHeaderView(options);
                },
                readonly: this.readonly
            }));
            this.subview('body', new BaseCollectionView({
                autoRender: true,
                el: this.$('.board-columns-container'),
                collection: this.columns,
                readonly: this.readonly,
                itemView: function(columnViewOptions) {
                    columnViewOptions.readonly = board.readonly;
                    columnViewOptions.boardCollection = board.boardCollection;
                    const boardColumnIds = columnViewOptions.model.get('ids');
                    const columnView = new board.columnView(_.extend(columnViewOptions, {
                        cardView: function(options) {
                            options.readonly = board.readonly;
                            options.actions = $.extend(true, {}, board.cardActions);
                            options.actionOptions = {
                                parameters: {
                                    boardColumnIds: JSON.stringify(boardColumnIds)
                                }
                            };
                            options.datagrid = board.boardPlugin.main.grid;
                            options.earlyTransitionStatusChangeTimeout = board.earlyChangeTimeout;
                            const card = new board.cardView(options);
                            board.listenTo(card, 'navigate', function retriggerNavigate(model, options) {
                                board.trigger('navigate', model,
                                    columnViewOptions.model.get('columnDefinition'), options);
                            });
                            return card;
                        }
                    }));
                    board.listenTo(columnView, 'move', board.onItemMove);
                    board.listenTo(columnView, 'dragStart', board.startIntensiveScrollTracking);
                    board.listenTo(columnView, 'dragEnd', board.stopIntensiveScrollTracking);
                    return columnView;
                }
            }));
            this.$('.board-body').on('scroll', this.onBoardBodyScroll.bind(this));

            this.loadingMask = new LoadingMaskView({
                container: this.$el
            });

            // link server request handlers
            this.listenTo(this.serverCollection, 'request', function(model, xhr) {
                this._beforeRequest();
                const self = this;
                const always = xhr.always;
                xhr.always = function(...args) {
                    always.apply(this, args);
                    if (!self.disposed) {
                        self._afterRequest(this);
                    }
                };
            });

            // setup scroll tracking logic
            this.setTrackScrollInterval = setInterval(this.trackScroll.bind(this), 300);
            this.listenTo(this.serverCollection, 'change reset add remove',
                _.debounce(this.trackScroll.bind(this), 0));
        },

        /**
         * Updates no-data block
         */
        updateNoDataBlock: function() {
            let messageHTML;
            const grid = this.boardPlugin.main.grid;
            const noDataVisible = this.serverCollection.models.length <= 0;
            if (noDataVisible) {
                const placeholders = {
                    entityHint: (grid.entityHint || __(grid.noDataTranslations.entityHint)).toLowerCase()
                };

                if (_.isEmpty(this.serverCollection.state.filters)) {
                    messageHTML = grid.noDataTemplate({
                        text: __(grid.noDataTranslations.noEntities, placeholders)
                    });
                } else {
                    messageHTML = grid.noSearchResultsTemplate({
                        title: __(grid.noDataTranslations.noResultsTitle),
                        text: __(grid.noDataTranslations.noResults, placeholders)
                    });
                }

                this.$('.no-data').html(messageHTML);
            }
            this.$el.toggleClass('no-data-visible', noDataVisible);
        },

        /**
         * Starts intensive scroll settings tracking, usefull during d'n'd when there is no enough events
         */
        startIntensiveScrollTracking: function() {
            this.dragTrackScrollInterval = setInterval(this.trackScroll.bind(this), 0);
        },

        /**
         * Stops intensive scroll settings tracking
         */
        stopIntensiveScrollTracking: function() {
            clearInterval(this.dragTrackScrollInterval);
        },

        /**
         * Returns css expression for board height, so board appearance will not break UI
         *
         * @return {string}
         */
        getCssHeightCalcExpression: function() {
            const documentHeight = scrollHelper.documentHeight();
            const availableHeight = mediator.execute('layout:getAvailableHeight', this.$('.board-body'));
            return 'calc(100vh - ' + (documentHeight - availableHeight) + 'px)';
        },

        /**
         * Scroll support handler
         */
        trackScroll: function() {
            const bodyEl = this.$('.board-body')[0];
            const hasScroll = scrollHelper.hasScroll(bodyEl, 'top');

            if (hasScroll !== this.lastHasScroll) {
                this.subview('header').$el.css({
                    paddingRight: hasScroll ? scrollHelper.scrollbarWidth() : 0
                });
                this.lastHasScroll = hasScroll;
            }

            $(bodyEl).css({
                maxHeight: this.getCssHeightCalcExpression()
            });
        },

        /**
         * Handles server data request start
         *
         * @private
         */
        _beforeRequest: function() {
            if (this.serverCollection.isLoadingMore) {
                this.$('.board-body').addClass('loading-more');
            } else {
                this.loadingMask.show();
            }
        },

        /**
         * Handles server data request end
         *
         * @private
         */
        _afterRequest: function() {
            this.loadingMask.hide();
            this.$('.board-body').removeClass('loading-more');
        },

        /**
         * Handler which persist DOM state to server
         */
        onItemMove: function(data) {
            const options = {
                position: data.position,
                column: data.column,
                relativePosition: {}
            };
            if (data.column.get('items').length) {
                if (data.position === 0) {
                    options.relativePosition.insertBefore = data.column.get('items').at(0);
                } else {
                    options.relativePosition.insertAfter = data.column.get('items')
                        .at(Math.min(data.position - 1, data.column.get('items').length - 1));
                }
            }
            // do not break $.sortable working cycle, let it finish everything it need
            _.defer(() => {
                this.trigger('update', data.model, options);
            });
        },

        /**
         * Callback keeps header up to date with scrollable body
         */
        onBoardBodyScroll: function() {
            const el = this.$('.board-body')[0];
            this.subview('header').$el.css({
                'margin-left': -1 * el.scrollLeft
            });
            if (el.scrollTop > el.scrollHeight - el.offsetHeight - 50) {
                this.trigger('loadMoreIfPossible');
            }
        }
    });

    return BoardView;
});
