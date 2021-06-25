define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const BaseCollection = require('oroui/js/app/models/base/collection');
    const PermissionModel = require('orouser/js/models/role/permission-model');
    const CapabilitiesView = require('orouser/js/views/role/capabilities-view');
    const accessLevels = require('orouser/js/constants/access-levels');
    const capabilityCategories = require('orouser/js/constants/capability-categories');

    const CapabilitySetComponent = BaseComponent.extend({
        /**
         * @type {Array<string>}
         */
        tabIds: null,

        /**
         * @inheritdoc
         */
        constructor: function CapabilitySetComponent(options) {
            CapabilitySetComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         * @param {Array<Object>} options.data collection of grouped capabilities
         * @param {string=} options.currentCategoryId by default it is 'all' category
         * @param {Array<string>} options.tabIds list of category Ids that are represented as tabs
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['tabIds']));
            const groups = _.map(options.data, function(group) {
                group.items = _.map(group.items, function(item) {
                    item.editable = !options.readonly;
                    return item;
                });
                if (options.readonly) {
                    group.items = _.map(group.items, function(item) {
                        item.editable = !options.readonly;
                        item.noAccess = item.access_level === accessLevels.NONE;
                        return item;
                    });
                }
                const itemsCollection = new BaseCollection(group.items, {
                    model: PermissionModel
                });
                this.listenTo(itemsCollection, 'change', this.onAccessLevelChange.bind(this, group.group));
                return _.extend({}, group, {
                    editable: !options.readonly,
                    items: itemsCollection
                });
            }, this);

            this.currentCategory = {
                id: options.currentCategoryId || capabilityCategories.COMMON
            };

            this.view = new CapabilitiesView({
                el: options._sourceElement,
                collection: new BaseCollection(groups),
                filterer: model => {
                    const group = model.get('group');
                    const currentCategory = this.currentCategory;
                    if (currentCategory.id === capabilityCategories.GENERAL) {
                        return group && !_.contains(options.tabIds, group);
                    }
                    return currentCategory.id === capabilityCategories.COMMON || group === currentCategory.id;
                }
            });

            this.listenTo(mediator, 'role:entity-category:changed', this.onCategoryChange);

            CapabilitySetComponent.__super__.initialize.call(this, options);
        },

        /**
         * Handles category change
         *  - updates local cache of current category
         *  - filters collection view
         *
         * @param {Object} category
         * @param {string} category.id
         */
        onCategoryChange: function(category) {
            _.extend(this.currentCategory, category);
            this.view.filter();
        },

        /**
         * Handles access level change of some capability in a group
         *
         * @param {string} group
         * @param {PermissionModel} model
         */
        onAccessLevelChange: function(group, model) {
            let category = group;
            if (category && !_.contains(this.tabIds, category)) {
                category = capabilityCategories.GENERAL;
            }
            mediator.trigger('securityAccessLevelsComponent:link:click', {
                accessLevel: model.get('access_level'),
                identityId: model.get('identity'),
                permissionName: model.get('name'),
                group: group,
                category: category,
                isInitialValue: !model.isAccessLevelChanged()
            });
        }
    });
    return CapabilitySetComponent;
});
