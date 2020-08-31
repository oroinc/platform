import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/datagrid/toggle-group.html';
import __ from 'orotranslation/js/translator';

/**
 * Updates grid's url params with options to turn on/off data pagination
 */
const TogglePaginationView = BaseView.extend({
    autoRender: true,

    enabled: true,

    grid: null,

    defaultGroupState: true, // grouped by default

    template,

    events: {
        'click [data-role="group-toggler"]': 'onClick'
    },

    constructor: function TogglePaginationView(options) {
        TogglePaginationView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.grid = options.datagrid;
        this.listenTo(this.grid.collection, 'updateState sync', this.render);
        this.listenTo(this.grid, 'disable', this.disable);
        this.listenTo(this.grid, 'enable', this.enable);

        TogglePaginationView.__super__.initialize.call(this, options);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.grid;

        TogglePaginationView.__super__.dispose.call(this);
    },

    getCurrentState() {
        const {parameters = {}} = this.grid.collection.state;
        return parameters.hasOwnProperty('group') ? parameters.group : this.defaultGroupState;
    },

    getTemplateData() {
        const isGrouped = this.getCurrentState();
        const translationPrefix = `oro.frontend.shoppinglist.btn.${isGrouped ? 'ungroup_similar' : 'group_similar'}`;
        return {
            enabled: this.enabled,
            visible: this.hasSimilar(),
            label: __(`${translationPrefix}.label`),
            ariaLabel: __(`${translationPrefix}.aria_label`)
        };
    },

    hasSimilar() {
        const groups = [];
        return this.grid.collection.models.some(model => {
            const groupId = model.get('_groupId');
            return groupId && (groups.indexOf(groupId) !== -1 || !groups.push(groupId));
        });
    },

    disable() {
        this.enabled = false;
        this.render();
        return this;
    },

    enable() {
        this.enabled = true;
        this.render();
        return this;
    },

    onClick() {
        if (!this.enabled) {
            return;
        }
        this.togglePagination();
    },

    togglePagination() {
        const {parameters = {}} = this.grid.collection.state;
        if (this.getCurrentState() === this.defaultGroupState) {
            parameters.group = !this.defaultGroupState;
        } else {
            delete parameters.group;
        }
        this.grid.collection.updateState({parameters});
        this.grid.collection.fetch({reset: true});
    }
});

export default TogglePaginationView;
