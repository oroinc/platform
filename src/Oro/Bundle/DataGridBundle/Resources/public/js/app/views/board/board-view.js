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
    var BoardView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var scrollHelper = require('oroui/js/tools/scroll-helper');

    BoardView = BaseView.extend({
        /**
         * @inheritDoc
         */
        className: 'board',

        /**
         * @inheritDoc
         */
        template: require('tpl!../../../../templates/board/board-view.html'),

        /**
         * Shared between shild views timeout to detect early status change
         */
        earlyChangeTimeout: 2000,

        /**
         * @inheritDoc
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
            this.listenTo(this.serverCollection, 'change reset add remove', this.updateNoDataBlock);
            BoardView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            clearInterval(this.setTrackScrollInterval);
            BoardView.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        _ensureElement: function() {
            BoardView.__super__._ensureElement.apply(this, arguments);
            if (this.className) {
                this.$el.addClass(_.result(this, 'className'));
            }
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var board = this;
            BoardView.__super__.render.call(this);
            this.updateNoDataBlock();
            this.subview('header', new BaseCollectionView({
                autoRender: true,
                el: this.$('.board-header'),
                collection: this.columns,
                itemView: this.columnHeaderView,
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
                    var columnView = new board.columnView(_.extend(columnViewOptions, {
                        cardView: function(options) {
                            options.readonly = board.readonly;
                            options.earlyTransitionStatusChangeTimeout = board.earlyChangeTimeout;
                            var card = new board.cardView(options);
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
                var self = this;
                var always = xhr.always;
                xhr.always = function() {
                    always.apply(this, arguments);
                    if (!self.disposed) {
                        self._afterRequest(this);
                    }
                };
            });

            // setup scroll tracking logic
            this.setTrackScrollInterval = setInterval(_.bind(this.trackScroll, this), 300);
            this.listenTo(this.serverCollection, 'change reset add remove',
                _.debounce(_.bind(this.trackScroll, this), 0));
        },

        /**
         * Updates no-data block
         */
        updateNoDataBlock: function() {
            var noDataVisible = this.serverCollection.models.length <= 0;
            if (noDataVisible) {
                var placeholders = {
                    entityHint: (this.boardPlugin.main.grid.entityHint || __('oro.datagrid.entityHint')).toLowerCase()
                };
                var message = _.isEmpty(this.serverCollection.state.filters) ?
                    'oro.datagrid.no.entities' : 'oro.datagrid.no.results';

                this.$('.no-data').html(this.boardPlugin.main.grid.noDataTemplate({
                    hint: __(message, placeholders).replace('\n', '<br />')
                }));
            }
            this.$el.toggleClass('no-data-visible', noDataVisible);
        },

        /**
         * Starts intensive scroll settings tracking, usefull during d'n'd when there is no enough events
         */
        startIntensiveScrollTracking: function() {
            this.dragTrackScrollInterval = setInterval(_.bind(this.trackScroll, this), 0);
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
            var documentHeight = scrollHelper.documentHeight();
            var availableHeight = mediator.execute('layout:getAvailableHeight', this.$('.board-body'));
            return 'calc(100vh - ' + (documentHeight - availableHeight) + 'px)';
        },

        /**
         * Scroll support handler
         */
        trackScroll: function() {
            var bodyEl = this.$('.board-body')[0];
            var hasScroll = scrollHelper.hasScroll(bodyEl, 'top');

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
            var options = {
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
            _.defer(_.bind(function() {
                this.trigger('update', data.model, options);
            }, this));
        },

        /**
         * Callback keeps header up to date with scrollable body
         */
        onBoardBodyScroll: function() {
            var el = this.$('.board-body')[0];
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
