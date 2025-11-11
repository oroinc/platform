import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';
import BaseCollection from 'oroui/js/app/models/base/collection';
import TabCollectionView from 'oroui/js/app/views/tab-collection-view';
import moduleConfig from 'module-config';

const config = {
    useDropdown: true,
    ...moduleConfig(module.id)
};

const EntityCategoryTabsComponent = BaseComponent.extend({
    /**
     * @type {Object<string, Object<string, boolean>>}
     */
    changesByCategory: null,

    /**
     * @inheritdoc
     */
    constructor: function EntityCategoryTabsComponent(options) {
        EntityCategoryTabsComponent.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} options
     * @param {Array<Object>} options.data collection of tabs build over entities category
     */
    initialize: function(options) {
        this.changesByCategory = {};

        let categories = options.data;
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

        const controlTabPanel = options.controlTabPanel;
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
            useDropdown: (options.useDropdown === void 0 ? config : options)['useDropdown']
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
        const permission = data.identityId + '::' + data.permissionName;
        const category = data.category;

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
        const particularCategory = this.categories.findWhere({id: category});
        if (particularCategory) {
            particularCategory.set('changed', !_.isEmpty(this.changesByCategory[category]));
        }
    }
});
export default EntityCategoryTabsComponent;
