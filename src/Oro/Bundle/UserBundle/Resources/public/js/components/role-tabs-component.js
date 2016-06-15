define(function(require) {
    'use strict';

    var RoleTabsComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var BaseCollection = require('oroui/js/app/models/base/collection')
    var DatagridComponent = require('orodatagrid/js/app/components/datagrid-component');
    var RoleTabsView = require('orouser/js/views/role-tabs-view');
    var CapabilityGroupView = require('orouser/js/views/capability-group-view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    RoleTabsComponent = BaseComponent.extend({
        categoriesCollection: null,
        particularTabs: null,
        initialize: function(options) {
            var categories  = _.where(_.map(options.categories, function(category, id) {
                return _.extend(category, {id: id});
            }), {system: false});
            this.particularTabs = _.pluck(categories, 'id');
            categories.unshift({
                label: __('oro.role.tabs.all.label'),
                id: 'all',
                multi: true
            });
            categories.push({
                label: __('oro.role.tabs.system_capabilities.label'),
                id: 'system',
                multi: true
            });
            this.categoriesCollection = new BaseCollection(categories);
            this.view = new RoleTabsView({
                el: options._sourceElement,
                autoRender: true,
                animationDuration: 0,
                collection: this.categoriesCollection
            });
            this.initCapabilityView(options);
            this.initPermissionGrid(options);
            this.categoriesCollection.at(0).set('active', true);
        },
        initCapabilityView: function(options) {
            var capabilityGroups = _.map(_.groupBy(options.capabilitiesData, 'group'), function(group, key) {
                var label = _.result(_.result(options.categories, key), 'label');
                return new BaseModel({group: key, label: label, items: new BaseCollection(group)});
            });
            this.capabilityView = new BaseCollectionView({
                el: this.view.$('.capability-set'),
                autoRender: true,
                animationDuration: 0,
                collection: new BaseCollection(capabilityGroups),
                categoriesCollection: this.categoriesCollection,
                itemView: CapabilityGroupView,
                filterer: this.filterer.bind(this)
            });
            this.categoriesCollection.on('change', _.bind(function() {
                this.capabilityView.filter();
            }, this));
        },
        initPermissionGrid: function(options) {
            this.permissionsGridComponent = new DatagridComponent(_.extend(options.gridOptions, {
                _sourceElement: this.view.$('.permissions-grid').get(0)
            }));
            this.permissionsGridComponent.built.then(_.bind(function(gridView) {
                gridView.body.filter(this.filterer.bind(this));
                this.categoriesCollection.on('change', function() {
                    gridView.body.filter();
                    gridView.$el.toggle(gridView.body.visibleItems.length > 0);
                });
            }, this));
        },
        filterer: function(item) {
            var itemGroup = item.get('group');
            var activeGroupModel = this.categoriesCollection.findWhere({'active': true});
            var activeGroup = activeGroupModel === void 0 ? 'all' : activeGroupModel.get('id');
            if (activeGroup === 'system') {
                return Boolean(itemGroup) && !_.contains(this.particularTabs, itemGroup);
            }
            return activeGroup === 'all' || activeGroup === itemGroup;
        }
    });
    return RoleTabsComponent;
});
