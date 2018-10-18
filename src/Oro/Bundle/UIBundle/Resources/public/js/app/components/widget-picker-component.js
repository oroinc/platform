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
        constructor: function WidgetPickerComponent() {
            WidgetPickerComponent.__super__.constructor.apply(this, arguments);
        },

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
                    el: $el.find('[data-role="widget-picker-container"]'),
                    filterModel: widgetPickerFilterModel,
                    listSelector: '[data-role="widget-picker-results"]',
                    fallbackSelector: '[data-role="widget-picker-no-results-found"]'
                })
            );
            this.widgetPickerFilterView = new WidgetPickerFilterView({
                el: $el.find('[data-role="widget-picker-filter"]'),
                model: widgetPickerFilterModel
            });
        }
    });

    return WidgetPickerComponent;
});
