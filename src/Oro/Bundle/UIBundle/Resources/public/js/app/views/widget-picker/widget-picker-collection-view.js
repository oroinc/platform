define(function(require) {
    'use strict';

    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const WidgetPickerItemView = require('oroui/js/app/views/widget-picker/widget-picker-item-view');
    const _ = require('underscore');

    const WidgetPickerCollectionView = BaseCollectionView.extend({
        itemView: WidgetPickerItemView,
        isWidgetLoadingInProgress: false,

        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerCollectionView(options) {
            WidgetPickerCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!options.loadWidget) {
                throw new Error('Missing required "loadWidget" option');
            }
            if (!options.filterModel) {
                throw new Error('Missing required "filterModel" option');
            }
            _.extend(this, _.pick(options, ['filterModel', 'loadWidget']));
            options.filterer = this.filterModel.filterer.bind(this.filterModel);
            WidgetPickerCollectionView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        delegateListeners: function() {
            this.listenTo(this.filterModel, 'change', this.filter);
            return WidgetPickerCollectionView.__super__.delegateListeners.call(this);
        },

        /**
         * @inheritdoc
         */
        initItemView: function(model) {
            const view = WidgetPickerCollectionView.__super__.initItemView.call(this, model);
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
            return () => {
                if (this.disposed) {
                    return;
                }
                this.isWidgetLoadingInProgress = false;
                _.each(this.getItemViews(), function(itemView) {
                    itemView.trigger('unblock_add_btn');
                });
            };
        }
    });

    return WidgetPickerCollectionView;
});
