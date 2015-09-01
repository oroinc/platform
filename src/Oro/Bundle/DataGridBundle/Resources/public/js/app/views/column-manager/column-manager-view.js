define(function(require) {
    'use strict';

    var ColumnManagerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var ColumnManagerItemView = require('./column-manager-item-view');

    ColumnManagerView = BaseCollectionView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager.html'),
        itemView: ColumnManagerItemView,

        className: 'column-manager action btn',
        listSelector: 'tbody',

        events: {
            'click .dropdown-menu': 'onDropdownClick',
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
            _.extend(this, _.pick(options, ['orderShift']));

            ColumnManagerView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @inheritDoc
         */
        render: function() {
            ColumnManagerView.__super__.render.apply(this, arguments);
            this.initSorting();
            return this;
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
         * Switches column manager into enable mode
         * (ActionLauncherInterface)
         */
        enable: function() {
            this.$el.removeClass('disabled');
        },

        /**
         * Switches column manager into disable mode
         * (ActionLauncherInterface)
         */
        disable: function() {
            this.$el.addClass('disabled');
        },

        /**
         * Prevents dropdown from closing on click
         *
         * @param {jQuery.Event} e
         */
        onDropdownClick: function(e) {
            e.stopPropagation();
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
                this.trigger('reordered');
            }
        }
    });

    return ColumnManagerView;
});
