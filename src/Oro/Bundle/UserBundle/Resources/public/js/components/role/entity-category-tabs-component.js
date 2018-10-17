define(function(require) {
    'use strict';

    var EntityCategoryTabsComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');

    EntityCategoryTabsComponent = BaseComponent.extend({
        /**
         * @type {Object<string, Object<string, boolean>>}
         */
        changesByCategory: null,

        /**
         * @inheritDoc
         */
        constructor: function EntityCategoryTabsComponent() {
            EntityCategoryTabsComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         * @param {Array<Object>} options.data collection of tabs build over entities category
         */
        initialize: function(options) {
            this.changesByCategory = {};

            var categories = options.data;
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

            var controlTabPanel = options.controlTabPanel;
            categories = _.each(categories, function(category) {
                category.uniqueId = _.uniqueId(category.id);
                if (typeof controlTabPanel === 'string') {
                    category.controlTabPanel = controlTabPanel;
                }
            }, this);

            this.categories = new BaseCollection(categories);

            this.view = new TabCollectionView({
                el: options._sourceElement,
                animationDuration: 0,
                collection: this.categories,
                useDropdown: options.useDropdown
            });

            this.listenTo(this.categories, 'change', this.onCategoryChange);
            this.listenTo(mediator, 'securityAccessLevelsComponent:link:click', this.onAccessLevelChange);
        },

        onCategoryChange: function(model) {
            if (model.hasChanged('active') && model.get('active') === true) {
                mediator.trigger('role:entity-category:changed', {
                    id: model.get('id')
                });
            }
        },

        onAccessLevelChange: function(data) {
            var permission = data.identityId + '::' + data.permissionName;
            var category = data.category;

            if (_.isUndefined(category)) {
                return;
            }

            // update changes information
            if (data.isInitialValue) {
                delete this.changesByCategory[category][permission];
                if (_.isEmpty(this.changesByCategory[category])) {
                    delete this.changesByCategory[category];
                }
            } else {
                if (!this.changesByCategory[category]) {
                    this.changesByCategory[category] = {};
                }
                this.changesByCategory[category][permission] = true;
            }

            // update tabs
            this.categories.findWhere({id: 'all'}).set('changed', !_.isEmpty(this.changesByCategory));
            var particularCategory = this.categories.findWhere({id: category});
            if (particularCategory) {
                particularCategory.set('changed', !_.isEmpty(this.changesByCategory[category]));
            }
        }
    });
    return EntityCategoryTabsComponent;
});
