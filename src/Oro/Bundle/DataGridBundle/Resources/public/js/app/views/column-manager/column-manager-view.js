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
            'click .dropdown-menu': 'onDropdownClick'
        },

        /**
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
            this.$el.sortable({
                cursor: 'move',
                delay: 25,
                opacity: 0.7,
                revert: 10,
                axis: 'y',
                containment: this.$el,
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
         * @param e
         */
        onDropdownClick: function(e) {
            e.stopPropagation();
        },

        /**
         * Handles sorting change event and update order attribute for each column
         */
        onReorder: function() {
            var columnsChain = {};
            var reordered = false;

            this.$('tbody tr[data-cid]').each(function(i) {
                columnsChain[this.getAttribute('data-cid')] = i;
            });

            _.each(this.subviews, function(view) {
                var order = columnsChain[view.cid];
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
