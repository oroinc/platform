define(function(require) {
    'use strict';

    /**
     * Renders an list of cards
     *
     * @param {Function} options.cardView - cardView to render cards
     * @param {Boolean}  options.readonly - specifies if cards should be draggable
     * @augments BaseView
     */
    var ColumnView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    ColumnView = BaseView.extend({
        /**
         * @inheritDoc
         */
        className: 'board-column',

        /**
         * @inheritDoc
         */
        template: require('tpl!../../../../templates/board/column-view.html'),

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.cardView = options.cardView;
            this.readonly = options.readonly;
            this.boardCollection = options.boardCollection;
            this.listenTo(this.boardCollection, 'add remove reset sort', this.cleanupViews);
            ColumnView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var column = this;
            if (this.subview('columns') && !this.readonly) {
                this.destroySortable();
            }
            ColumnView.__super__.render.call(this);
            this.subview('columns', new BaseCollectionView({
                autoRender: true,
                el: this.$el,
                collection: this.model.get('items'),
                itemView: this.cardView,
                animationDuration: 0
            }));
            if (!this.readonly) {
                this.listenTo(this.model.get('items'), 'change reset add remove', function() {
                    column.$el.sortable('refresh');
                });
                this.initSortable();
            }
        },

        /**
         * Connects sortable behaviour
         */
        initSortable: function() {
            var column = this;
            this.subview('columns').$el.sortable({
                connectWith: '.board-column',
                placeholder: 'board-card-placeholder',
                cancel: '[data-non-valid]',
                forcePlaceholderSize: true,
                start: function() {
                    $(document.body).addClass('force-grabbed-cursor');
                    column.trigger('dragStart');
                },
                stop: function() {
                    $(document.body).removeClass('force-grabbed-cursor');
                    column.trigger('dragEnd');
                },
                update: function(event, data) {
                    var domEl = data.item[0];
                    var dropIndex = Array.prototype.indexOf.call(domEl.parentNode.children, domEl);
                    if (domEl.parentNode === column.el) {
                        if (!data.item.data('model')) {
                            throw new Error('Trying to receive non card element');
                        }
                        column.trigger('move', {
                            model: data.item.data('model'),
                            column: column.model,
                            position: dropIndex
                        });
                    } else {
                        // we will be unable to detect position of element later
                        // store value for later usage
                        ColumnView._lastDropIndex = dropIndex;
                    }
                    // all updates must go through data updates
                    // don't allow sortable update DOM
                    return false;
                },
                receive: function(event, data) {
                    if (!data.item.data('model')) {
                        throw new Error('Trying to receive non card element');
                    }
                    column.trigger('move', {
                        model: data.item.data('model'),
                        column: column.model,
                        position: ColumnView._lastDropIndex
                    });
                    return false;
                },
                over: function() {
                    column.$el.addClass('drag-over');
                },
                out: function() {
                    column.$el.removeClass('drag-over');
                }
            });
        },

        /**
         * Disconnects sortable behaviour
         */
        destroySortable: function() {
            this.subview('columns').$el.sortable('destroy');
        },

        cleanupViews: function() {
            var columns = this.subview('columns');
            if (columns) {
                columns.cleanup();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.subview('columns') && !this.readonly) {
                this.destroySortable();
            }
            ColumnView.__super__.dispose.call(this);
        }
    });

    return ColumnView;
});
