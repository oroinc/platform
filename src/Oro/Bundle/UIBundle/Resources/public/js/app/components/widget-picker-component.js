define(function(require) {
    'use strict';

    var WidgetPickerComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var WidgetPickerFilterModel = require('oroui/js/app/models/widget-picker/widget-picker-filter-model');
    var WidgetPickerCollectionView = require('oroui/js/app/views/widget-picker/widget-picker-collection-view');
    var WidgetPickerFilterView = require('oroui/js/app/views/widget-picker/widget-picker-filter-view');
    var _ = require('underscore');

    /**
     * @export oroui/js/app/components/widget-picker-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.WidgetPickerComponent
     */
    WidgetPickerComponent = BaseComponent.extend({

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this._createViews(options);
            WidgetPickerComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         *
         * @param {Array} options
         * @protected
         */
        _createViews: function(options) {
            var $el = options._sourceElement;
            var widgetPickerFilterModel = new WidgetPickerFilterModel();
            this.widgetPickerCollectionView = new WidgetPickerCollectionView(
                _.defaults(options, {
                    el: $el.find('.widget-picker-containers'),
                    filterModel: widgetPickerFilterModel
                })
            );
            this.widgetPickerFilterView = new WidgetPickerFilterView({
                el: $el.find('.widget-picker-search'),
                model: widgetPickerFilterModel
            });
        }
    });

    return WidgetPickerComponent;
});
