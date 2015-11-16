define(function(require) {
    'use strict';

    var ColumnManagerCollectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var ColumnManagerItemView = require('./column-manager-item-view');

    ColumnManagerCollectionView = BaseCollectionView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager-collection.html'),
        itemView: ColumnManagerItemView,

        className: 'dropdown-menu',
        listSelector: 'tbody',
        fallbackSelector: '.column-manager-no-columns',

        events: {
            'click tbody tr [data-role=moveUp]': 'onMoveUp',
            'click tbody tr [data-role=moveDown]': 'onMoveDown'
        },

        /**
         * Number that is used to normalize order value
         *
         * @type {number}
         */
        orderShift: 0,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['orderShift', 'filterModel']));

            ColumnManagerCollectionView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @inheritDoc
         */
        render: function() {
            ColumnManagerCollectionView.__super__.render.apply(this, arguments);
            this.initSorting();
            return this;
        },

        initItemView: function() {
            var itemView = ColumnManagerCollectionView.__super__.initItemView.apply(this, arguments);
            itemView.setFilterModel(this.filterModel);
            return itemView;
        },

        /**
         * Initializes sorting widget for root element
         *  - allows to reorder columns
         */
        initSorting: function() {
            this.$('tbody').sortable({
                cursor: 'move',
                delay: 25,
                opacity: 0.7,
                revert: 10,
                axis: 'y',
                containment: this.$('tbody'),
                items: 'tr',
                tolerance: 'pointer',
                helper: function(e, ui) {
                    ui.children().each(function() {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                stop: _.bind(this.onReorder, this)
            }).disableSelection();
        },

        /**
         * Reorders columns elements (moves the element up)
         *
         * @param {jQuery.Event} e
         */
        onMoveUp: function(e) {
            var $elem = this.$(e.currentTarget).closest('tr');
            var $prev = $elem.prev();
            if ($prev.length) {
                $elem.insertBefore($prev);
                this.onReorder();
            }
        },

        /**
         * Reorders columns elements (moves the element down)
         *
         * @param {jQuery.Event} e
         */
        onMoveDown: function(e) {
            var $elem = this.$(e.currentTarget).closest('tr');
            var $next = $elem.next();
            if ($next.length) {
                $elem.insertAfter($next);
                this.onReorder();
            }
        },

        /**
         * Handles sorting change event and update order attribute for each column
         */
        onReorder: function() {
            var reordered = false;
            var columnsElements = this.$('tbody tr').toArray();

            _.each(this.subviews, function(view) {
                var order = columnsElements.indexOf(view.el) + this.orderShift;
                if (view.model.get('order') !== order) {
                    reordered = true;
                    view.model.set('order', order);
                }
            }, this);

            if (reordered) {
                this.collection.sort();
                this.trigger('reordered');
            }
        }
    });

    return ColumnManagerCollectionView;
});
