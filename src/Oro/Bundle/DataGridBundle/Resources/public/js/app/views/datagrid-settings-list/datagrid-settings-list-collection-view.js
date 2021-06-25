define(function(require, exports, module) {
    'use strict';

    const template = require('tpl-loader!orodatagrid/templates/datagrid-settings/datagrid-settings-collection.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const DatagridSettingsListFilterModel =
        require('orodatagrid/js/app/models/datagrid-settings-list/datagrid-settings-list-filter-model');
    const DatagridSettingsListItemView =
        require('orodatagrid/js/app/views/datagrid-settings-list/datagrid-settings-list-item-view');
    let config = require('module-config').default(module.id);
    require('jquery-ui/widgets/sortable');

    config = _.extend({
        fallbackSelector: '.no-data'
    }, config);

    /**
     * @class DatagridSettingsListCollectionView
     * @extends BaseCollectionView
     */
    const DatagridSettingsListCollectionView = BaseCollectionView.extend({
        animationDuration: 0,
        template: template,
        itemView: DatagridSettingsListItemView,

        className: 'dropdown-menu',
        listSelector: 'tbody',
        fallbackSelector: config.fallbackSelector,

        /**
         * @inheritdoc
         */
        events: {
            'click tbody tr [data-role=moveUp]': 'onMoveUp',
            'click tbody tr [data-role=moveDown]': 'onMoveDown'
        },

        /**
         * @inheritdoc
         */
        listen: {
            'change collection': 'filter',
            'visibilityChange': 'updateHeaderWidths',
            'layout:reposition mediator': 'updateHeaderWidths'
        },

        /**
         * Number that is used to normalize order value
         *
         * @type {number}
         */
        orderShift: 0,

        /**
         * @type {DatagridSettingsListFilterModel}
         */
        filterModel: null,

        /**
         * Check if sorting enabled
         *
         * @type {boolean}
         */
        addSorting: true,

        /**
         * @inheritdoc
         */
        constructor: function DatagridSettingsListCollectionView(options) {
            DatagridSettingsListCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['orderShift', 'filterModel', 'addSorting']));
            if (!(this.filterModel instanceof DatagridSettingsListFilterModel)) {
                throw new TypeError('Invalid required option "filterModel"');
            }

            options.filterer = this.filterModel.filterer.bind(this.filterModel);
            DatagridSettingsListCollectionView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        delegateListeners: function() {
            this.listenTo(this.filterModel, 'change', this.filter);
            return DatagridSettingsListCollectionView.__super__.delegateListeners.call(this);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            DatagridSettingsListCollectionView.__super__.render.call(this);
            if (this.addSorting) {
                this.initSorting();
            }
            this.updateHeaderWidths();
            return this;
        },


        initItemView: function(...args) {
            const itemView = DatagridSettingsListCollectionView.__super__.initItemView.apply(this, args);
            itemView.setFilterModel(this.filterModel);
            itemView.setSorting(this.addSorting);
            return itemView;
        },

        /**
         * @inheritdoc
         * @returns {*}
         */
        getTemplateData: function() {
            const data = DatagridSettingsListCollectionView.__super__.getTemplateData.call(this);
            data.addSorting = this.addSorting;
            return data;
        },

        /**
         * Initializes sorting widget for root element
         *  - allows to reorder columns
         */
        initSorting: function() {
            let placeholder;
            this.$('tbody').sortable({
                cursor: 'move',
                delay: 50,
                revert: 10,
                axis: 'y',
                containment: this.$('tbody'),
                items: 'tr',
                tolerance: 'pointer',
                handle: '.handle',
                helper: (e, ui) => {
                    placeholder = $('<tr />', {'class': 'sortable-placeholder'});
                    ui.children().each(function() {
                        const width = $(this).width();
                        $(this).width(width);
                        placeholder.append(
                            $('<td />').append(
                                $('<div/>').width(width)
                            )
                        );
                    });
                    ui.parent().append(placeholder);
                    return ui;
                },
                stop: () => {
                    placeholder.remove();
                    this.onReorder();
                }
            }).disableSelection();
        },

        /**
         * Reorders columns elements (moves the element up)
         *
         * @param {jQuery.Event} e
         */
        onMoveUp: function(e) {
            const $elem = this.$(e.currentTarget).closest('tr');
            const $prev = $elem.prev();
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
            const $elem = this.$(e.currentTarget).closest('tr');
            const $next = $elem.next();
            if ($next.length) {
                $elem.insertAfter($next);
                this.onReorder();
            }
        },

        /**
         * Handles sorting change event and update order attribute for each column
         */
        onReorder: function() {
            let reordered = false;
            const columnsElements = this.$('tbody tr').toArray();

            _.each(this.subviews, function(view) {
                const order = columnsElements.indexOf(view.el) + this.orderShift;
                if (view.model.get('order') !== order) {
                    reordered = true;
                    view.model.set('order', order);
                }
            }, this);

            if (reordered) {
                this.collection.sort();
                this.trigger('reordered');
            }
        },

        /**
         * @inheritdoc
         */
        toggleFallback: function() {
            const hasVisibleItems = Boolean(this.visibleItems.length);
            // to hide table's header once no visible data
            this.$('[data-role="datagrid-settings-table-header-wrapper"], ' +
                '[data-role="datagrid-settings-table-wrapper"]')
                .toggle(hasVisibleItems);
            DatagridSettingsListCollectionView.__super__.toggleFallback.call(this);
        },

        updateHeaderWidths: function() {
            let i;
            const $wrapper = this.$('[data-role="datagrid-settings-table-wrapper"]');
            const $table = $wrapper.children('table');
            const tableThs = $table.find('thead th');
            const headerThs = this.$('[data-role="datagrid-settings-table-header-wrapper"] tr th');
            $wrapper.css(`padding-${_.isRTL() ? 'left' : 'right'}`, 0);
            const clientWidth = $wrapper[0].clientWidth;
            if (clientWidth > 0) {
                $wrapper.css(
                    `padding-${_.isRTL() ? 'left' : 'right'}`,
                    $table.width() - $wrapper[0].clientWidth + 'px'
                );
            }
            for (i = 0; i < tableThs.length - 1; i += 1) {
                $(headerThs[i]).width($(tableThs[i]).width());
            }
        }
    });

    return DatagridSettingsListCollectionView;
});
