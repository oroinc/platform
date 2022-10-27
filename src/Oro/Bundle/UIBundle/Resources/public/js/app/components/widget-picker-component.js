define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const WidgetPickerFilterModel = require('oroui/js/app/models/widget-picker/widget-picker-filter-model');
    const WidgetPickerCollectionView = require('oroui/js/app/views/widget-picker/widget-picker-collection-view');
    const WidgetPickerFilterView = require('oroui/js/app/views/widget-picker/widget-picker-filter-view');
    const _ = require('underscore');

    /**
     * @export oroui/js/app/components/widget-picker-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.WidgetPickerComponent
     */
    const WidgetPickerComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerComponent(options) {
            WidgetPickerComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this._createViews(options);
            WidgetPickerComponent.__super__.initialize.call(this, options);
        },

        /**
         *
         * @param {Array} options
         * @protected
         */
        _createViews: function(options) {
            const $el = options._sourceElement;
            const widgetPickerFilterModel = new WidgetPickerFilterModel();
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
