define(function(require) {
    'use strict';

    var CustomColumnManagerComponent;

    var ColumnManagerComponent = require('orodatagrid/js/app/components/column-manager-component');

    /**
     * @class ColumnManagerComponent
     * @extends ColumnManagerComponent
     */
    CustomColumnManagerComponent = ColumnManagerComponent.extend({
        templateSelectors: {
            'columnManagerTpl': '#commerce-column-manager-tpl',
            'columnManagerCollectionsTpl': '#commerce-column-manager-collection-tpl',
            'columnManagerItemTpl': '#commerce-column-manager-item-tpl'
        },

        /**
         * @inheritDoc
         * */
        initialize: function(options) {
            options = options || {};

            options.templateSelectors = this.templateSelectors;
            CustomColumnManagerComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            delete this.templateSelectors;

            CustomColumnManagerComponent.__super__.dispose.apply(this, arguments);
        }
    });

    return CustomColumnManagerComponent;
});
