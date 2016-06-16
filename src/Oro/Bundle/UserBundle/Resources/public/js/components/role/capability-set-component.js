define(function(require) {
    'use strict';

    var CapabilitySetComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var CapabilitiesView = require('orouser/js/views/role/capabilities-view');

    CapabilitySetComponent = BaseComponent.extend({
        /**
         * @param {Object} options
         * @param {Array<Object>} options.data collection of grouped capabilities
         * @param {string=} options.currentCategoryId by default it is 'all' category
         * @param {Array<string>} options.tabIds list of category Ids that are represented as tabs
         */
        initialize: function(options) {
            var groups = _.map(options.data, function(group) {
                return _.extend({}, group, {
                    items: new BaseCollection(group.items)
                });
            });

            var currentCategory = {
                id: options.currentCategoryId || 'all'
            };

            this.view = new CapabilitiesView({
                el: options._sourceElement,
                collection: new BaseCollection(groups),
                filterer: function(model) {
                    var group = model.get('group');
                    if (currentCategory.id === 'system_capabilities') {
                        return Boolean(group) && !_.contains(options.tabIds, group);
                    }
                    return currentCategory.id === 'all' || group === currentCategory.id;
                }
            });

            this.listenTo(mediator, 'role:entity-category:changed', function(category) {
                _.extend(currentCategory, category);
                this.view.filter();
            });
        }
    });
    return CapabilitySetComponent;
});
