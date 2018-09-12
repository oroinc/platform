define(function(require) {
    'use strict';

    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var WidgetPickerItemView = require('oroui/js/app/views/widget-picker/widget-picker-item-view');
    var _ = require('underscore');

    var WidgetPickerCollectionView = BaseCollectionView.extend({
        itemView: WidgetPickerItemView,
        isWidgetLoadingInProgress: false,

        /**
         * @inheritDoc
         */
        constructor: function WidgetPickerCollectionView() {
            WidgetPickerCollectionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!options.loadWidget) {
                throw new Error('Missing required "loadWidget" option');
            }
            if (!options.filterModel) {
                throw new Error('Missing required "filterModel" option');
            }
            _.extend(this, _.pick(options, ['filterModel', 'loadWidget']));
            options.filterer = _.bind(this.filterModel.filterer, this.filterModel);
            WidgetPickerCollectionView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        delegateListeners: function() {
            this.listenTo(this.filterModel, 'change', this.filter);
            return WidgetPickerCollectionView.__super__.delegateListeners.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initItemView: function() {
            var view = WidgetPickerCollectionView.__super__.initItemView.apply(this, arguments);
            this.listenTo(view, 'widget_add', this.processWidgetAdd);
            view.setFilterModel(this.filterModel);
            return view;
        },

        /**
         *
         * @param {WidgetPickerModel} widgetModel
         * @param {WidgetPickerItemView} widgetPickerItemView
         */
        processWidgetAdd: function(widgetModel, widgetPickerItemView) {
            if (!this.isWidgetLoadingInProgress) {
                this.isWidgetLoadingInProgress = true;
                this.loadWidget(widgetModel, this._startLoading());
                widgetPickerItemView.trigger('start_loading');
                _.each(this.getItemViews(), function(itemView) {
                    if (itemView.cid !== widgetPickerItemView.cid) {
                        itemView.trigger('block_add_btn');
                    }
                });
            }
        },

        /**
         *
         * @returns {Function}
         * @protected
         */
        _startLoading: function() {
            return _.bind(function() {
                if (this.disposed) {
                    return;
                }
                this.isWidgetLoadingInProgress = false;
                _.each(this.getItemViews(), function(itemView) {
                    itemView.trigger('unblock_add_btn');
                });
            }, this);
        }
    });

    return WidgetPickerCollectionView;
});
