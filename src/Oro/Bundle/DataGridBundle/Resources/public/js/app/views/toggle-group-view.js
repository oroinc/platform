import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/datagrid/toggle-group.html';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';

/**
 * Updates grid's url params with options to turn on/off data pagination
 */
const TogglePaginationView = BaseView.extend({
    autoRender: true,

    enabled: true,

    grid: null,

    className: 'datagrid-toggle-group datagrid-divider',

    defaultGroupState: false, // ungrouped by default

    translationPrefix: 'oro.datagrid.btn',

    template,

    events: {
        'click [data-role="group-toggler"]': 'onClick'
    },

    constructor: function TogglePaginationView(options) {
        TogglePaginationView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        _.extend(this, _.pick(options, ['translationPrefix']));
        const {parameters = {}} = options.datagrid.collection.initialState;
        if (parameters.hasOwnProperty('group')) {
            this.defaultGroupState = parameters.group;
        }
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
        const translationPrefix = `${this.translationPrefix}.${isGrouped ? 'ungroup_similar' : 'group_similar'}`;

        return {
            enabled: this.enabled,
            visible: this.isVisible(),
            label: __(`${translationPrefix}.label`),
            ariaLabel: __(`${translationPrefix}.aria_label`)
        };
    },

    isVisible() {
        return this.grid.metadata.canBeGrouped || false;
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

    render() {
        TogglePaginationView.__super__.render.call(this);
        this.$el.toggleClass('empty', !this.isVisible());

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
        parameters.group = !this.getCurrentState();
        this.grid.collection.updateState({parameters});
        this.grid.collection.fetch({reset: true});
    }
});

export default TogglePaginationView;
