define(function(require) {
    'use strict';

    var EntityCategoryTabsComponent;
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');

    EntityCategoryTabsComponent = BaseComponent.extend({
        /**
         * @param {Object} options
         * @param {Array<Object>} options.data collection of tabs build over entities category
         */
        initialize: function(options) {
            var categories  = options.data;
            categories.unshift({
                id: 'all',
                label: __('oro.role.tabs.all.label'),
                active: true,
                multi: true
            });
            categories.push({
                id: 'system_capabilities',
                label: __('oro.role.tabs.system_capabilities.label'),
                multi: true
            });

            categories = new BaseCollection(categories);

            this.view = new TabCollectionView({
                el: options._sourceElement,
                animationDuration: 0,
                collection: categories
            });

            this.listenTo(categories, 'change', this.onCategoryChange);
        },

        onCategoryChange: function(model) {
            if (model.hasChanged('active') && model.get('active') === true) {
                mediator.trigger('role:entity-category:changed', {
                    id: model.get('id')
                });
            }
        }
    });
    return EntityCategoryTabsComponent;
});
