import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import Backgrid from 'backgrid';
import tools from 'oroui/js/tools';
import moduleConfig from 'module-config';
import baseMarkup from 'tpl-loader!orodatagrid/templates/datagrid/action-cell-base-markup.html';

const config = {
    showCloseButton: false,
    allowDefaultAriaLabel: true,
    ...moduleConfig(module.id)
};

/**
 * Cell for grid, contains actions
 *
 * @export  oro/datagrid/cell/action-cell
 * @class   oro.datagrid.cell.ActionCell
 * @extends Backgrid.Cell
 */
const ActionCell = Backgrid.Cell.extend({
    /** @property */
    className: 'action-cell',

    /** @property {Array} */
    actions: undefined,

    /** @property {boolean} */
    isDropdownActions: null,

    /** @property Integer */
    actionsHideCount: 3,

    /** @property {Array} */
    launchers: undefined,

    /** @property boolean */
    showCloseButton: config.showCloseButton,

    /** @property {string}: 'icon-text' | 'icon-only' | 'text-only' */
    launcherMode: '',

    /** @property {string}: 'icon-text' | 'icon-only' | 'text-only' */
    dropdownLauncherMode: '',

    /**
     * Allow launcher to use / set default aria-label attribute if it is not defined
     *
     * @property boolean
     * */
    allowDefaultAriaLabel: config.allowDefaultAriaLabel,

    /** @property {string}: 'show' | 'hide' */
    actionsState: '',

    /** @property */
    baseMarkup,

    /** @property */
    simpleBaseMarkup: _.template(`<div class="more-bar-holder action-row"></div>`),

    /** @property */
    closeButtonTemplate: _.template(
        `<li class="dropdown-close"><i class="fa-close hide-text">${__('Close')}</i></li>`
    ),

    /** @property */
    launchersContainerSelector: '.launchers-dropdown-menu',

    /** @property */
    launchersListTemplate: _.template(
        `<% if (withIcons) { %>
            <li><ul class="launchers-list"></ul></li>
        <% } else { %>
            <li class="well-small"><ul class="list-unstyled launchers-list"></ul></li>
        <% } %>`
    ),

    /** @property */
    simpleLaunchersListTemplate: _.template(
        `<% if (withIcons) { %>
            <ul class="nav nav--block nav-pills icons-holder launchers-list"></ul>
        <% } else { %>
            <ul class="unstyled launchers-list"></ul>
        <% } %>`
    ),

    /** @property */
    launcherItemTemplate: _.template(
        `<li class="launcher-item<% if (className) { %> <%- 'mode-' + className %><% } %>"></li>`
    ),

    /** @property */
    events: {
        'click': '_showDropdown',
        'keydown .dropdown-menu': 'onKeydown',
        'click .dropdown-close .fa-close': '_hideDropdown'
    },

    /**
     * @inheritdoc
     */
    constructor: function ActionCell(options) {
        ActionCell.__super__.constructor.call(this, options);
    },

    /**
     * Initialize cell actions and launchers
     */
    initialize: function(options) {
        const opts = this.opts = options || {};
        this.subviews = [];

        if (opts.actionsHideCount !== void 0) {
            this.actionsHideCount = opts.actionsHideCount;
        }

        if (opts.themeOptions.actionsHideCount !== void 0) {
            this.actionsHideCount = opts.themeOptions.actionsHideCount;
        }

        if (opts.allowDefaultAriaLabel !== void 0) {
            this.allowDefaultAriaLabel = opts.allowDefaultAriaLabel;
        }

        ActionCell.__super__.initialize.call(this, options);
        this.actions = this.createActions();

        this.model.set('availableActions', this.actions);

        this.isDropdownMode();

        _.each(this.actions, function(action) {
            this.listenTo(action, 'preExecute', this.onActionRun);
        }, this);

        this.listenTo(this.model, 'change:action_configuration', this.onActionConfigChange);

        this.subviews.push(...this.actions);
    },

    isDropdownMode() {
        this.isDropdownActions = this.model.get('availableActions').length >= this.actionsHideCount;

        if (_.isObject(this.opts.themeOptions.launcherOptions)) {
            this.launcherMode = this.opts.themeOptions.launcherOptions.launcherMode || this.launcherMode;
            this.actionsState = this.opts.themeOptions.launcherOptions.actionsState || this.actionsState;

            if (this.isDropdownActions) {
                this.launcherMode =
                    this.opts.themeOptions.launcherOptions.dropdownLauncherMode || this.launcherMode;
            }
        }

        return this.isDropdownActions;
    },

    /**
     * @inheritdoc
     */
    delegateEvents(events) {
        ActionCell.__super__.delegateEvents.call(this, events);

        if (!tools.isTouchDevice()) {
            this.$el.on(`mouseover${this.eventNamespace()}`, '.dropdown-toggle', this._showDropdown.bind(this));
            this.$el.on(`mouseleave${this.eventNamespace()}`, '.dropleft.show', this._hideDropdown.bind(this));
        }

        return this;
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }
        delete this.actions;
        delete this.column;
        ActionCell.__super__.dispose.call(this);
    },

    /**
     * Handle action run
     *
     * @param {oro.datagrid.action.AbstractAction} action
     */
    onActionRun: function(action) {
        this.$('.show > [data-toggle="dropdown"]').trigger('tohide.bs.dropdown');
    },

    /**
     * Creates actions
     *
     * @return {Array}
     */
    createActions: function() {
        const result = [];
        const actions = this.column.get('actions');
        const config = this.model.get('action_configuration') || {};

        _.each(actions, function(action, name) {
            // filter available actions for current row
            if (!config || config[name] !== false) {
                result.push(this.createAction(action, {...(config[name] || {}), name}));
            }
        }, this);

        return _.sortBy(result, 'order');
    },

    /**
     * Creates action
     *
     * @param {Function} Action
     * @param {Object} configuration
     * @protected
     */
    createAction: function(Action, configuration) {
        return new Action({
            model: this.model,
            datagrid: this.column.get('datagrid'),
            configuration: configuration
        });
    },

    /**
     * Creates actions launchers
     *
     * @protected
     */
    createLaunchers: function() {
        return this.actions.map(action => {
            return action.launcherInstance || action.createLauncher({
                launcherMode: this.launcherMode,
                allowDefaultAriaLabel: this.allowDefaultAriaLabel
            });
        });
    },

    /**
     * Handles `action_configuration` attributes change and updates actions list accordingly
     */
    onActionConfigChange: function() {
        const config = this.model.get('action_configuration') || {};

        // update existing actions
        this.actions.forEach(action => {
            const isEnabled = config[action.configuration.name];
            if (isEnabled !== void 0 && isEnabled !== action.launcherInstance.enabled) {
                action.launcherInstance[isEnabled ? 'enable' : 'disable']();
            }
        });

        // create newly enabled actions
        Object.entries(config).forEach(([name, isEnabled]) => {
            if (!isEnabled) {
                return; // action is not enabled -- no need for a check if it exists
            }
            let action = this.actions.find(action => action.configuration.name === name);
            const Action = this.column.get('actions')[name];
            if (!action && Action) {
                action = this.createAction(Action, {...(config[name] || {}), name});
                action.createLauncher({
                    launcherMode: this.launcherMode,
                    allowDefaultAriaLabel: this.allowDefaultAriaLabel
                });
                this.actions.push(action);
            }
        });

        // re-sort actions to preserve order of declaration and sort by order value afterwards, if it's defined
        const actions = Object.keys(this.column.get('actions'))
            .map(name => this.actions.find(action => action.configuration.name === name));
        this.actions.length = 0;
        this.actions.push(..._.sortBy(_.compact(actions), 'order'));

        const isDropdownActions = this.isDropdownActions;
        this.model.set('availableActions', this.actions.filter(action => action.launcherInstance?.enabled));
        this.isDropdownMode();

        if (isDropdownActions !== this.isDropdownActions) {
            // Patch existing actions
            this.actions.forEach(action => action.launcherInstance.launcherMode = this.launcherMode);
            this.render();
        }

        this.isLauncherListFilled = false;
        this.fillLauncherList();
    },

    /**
     * Render cell with actions
     */
    render: function() {
        // don't render anything if list of launchers is empty
        if (_.isEmpty(this.actions)) {
            this.$el.attr({
                'aria-label': null,
                'data-blank-content': null,
                'aria-colindex': null
            }).empty();

            return this;
        }

        if (this.actionsState === 'show') {
            this.isDropdownActions = false;
        } else if (this.actionsState === 'hide') {
            this.isDropdownActions = true;
        }

        // Reset properties to allow rendering actions as a dropdown or list, and vice versa
        delete this.baseMarkup;
        delete this.launchersListTemplate;
        delete this.launchersContainerSelector;

        if (!this.isDropdownActions) {
            this.baseMarkup = this.simpleBaseMarkup;
            this.launchersListTemplate = this.simpleLaunchersListTemplate;
            this.launchersContainerSelector = '.more-bar-holder';
        }

        this.$el.html(this.baseMarkup(this.getTemplateData()));
        this.isLauncherListFilled = false;

        if (!this.isDropdownActions) {
            this.fillLauncherList();
        }

        this.$el.toggleClass('dropdown-action-cell', this.isDropdownActions);

        return this;
    },

    getTemplateData: function() {
        return {
            togglerId: 'actions-cell-dropdown-' + this.cid,
            label: __('oro.datagrid.row_actions.label')
        };
    },

    fillLauncherList: function() {
        if (!this.isLauncherListFilled) {
            this.isLauncherListFilled = true;

            let launcherList = this.createLaunchers();
            launcherList.forEach(launcher => launcher.$el.detach());
            launcherList = launcherList.filter(launcher => launcher.enabled);

            const launchers = this.getLaunchersByIcons(launcherList);
            const $listsContainer = this.$(this.launchersContainerSelector);
            $listsContainer.empty();

            if (this.showCloseButton && launcherList.length >= this.actionsHideCount) {
                $listsContainer.append(this.closeButtonTemplate());
            }

            if (launchers.withIcons.length) {
                this.renderLaunchersList(launchers.withIcons, {withIcons: true})
                    .appendTo($listsContainer);
            }

            if (launchers.withIcons.length && launchers.withoutIcons.length) {
                const divider = document.createElement($listsContainer[0].tagName === 'UL' ? 'li' : 'span');

                divider.classList.add('divider');
                $listsContainer.append(divider);
            }

            if (launchers.withoutIcons.length) {
                this.renderLaunchersList(launchers.withoutIcons, {withIcons: false})
                    .appendTo($listsContainer);
            }
        }
    },

    /**
     * Render launchers list
     *
     * @param {Array} launchers
     * @param {Object=} params
     * @return {jQuery} Rendered element wrapped with jQuery
     */
    renderLaunchersList: function(launchers, params) {
        params = params || {};
        const result = $(this.launchersListTemplate(params));
        const $launchersList = result.filter('.launchers-list').length ? result : $('.launchers-list', result);
        _.each(launchers, function(launcher) {
            $launchersList.append(this.renderLauncherItem(launcher));
        }, this);

        return result;
    },

    /**
     * Render launcher
     *
     * @param {orodatagrid.datagrid.ActionLauncher} launcher
     * @param {Object=} params
     * @return {jQuery} Rendered element wrapped with jQuery
     */
    renderLauncherItem: function(launcher, params) {
        params = _.extend(params || {}, {className: launcher.launcherMode});
        const result = $(this.launcherItemTemplate(params));
        const $launcherItem = result.filter('.launcher-item').length ? result : $('.launcher-item', result);
        $launcherItem.append(launcher.render().$el);
        const className = 'mode-' + launcher.launcherMode;
        $launcherItem.addClass(className);
        this.decorateLauncherItem(launcher);
        return result;
    },

    /**
     * Add extra classes to launcher
     *
     * @param {orodatagrid.datagrid.ActionLauncher} launcher
     * @return {Object}
     */
    decorateLauncherItem(launcher) {
        if (!launcher.$el) {
            return this;
        }

        if (this.isDropdownActions) {
            launcher.$el.addClass('dropdown-item');
        }

        return this;
    },

    /**
     * Get separate object of launchers arrays: with icons (key `withIcons`) and without icons (key `withoutIcons`).
     *
     * @return {Object}
     * @protected
     */
    getLaunchersByIcons: function(launcherList) {
        const launchers = {
            withIcons: [],
            withoutIcons: []
        };

        _.each(launcherList, function(launcher) {
            if (launcher.icon) {
                launchers.withIcons.push(launcher);
            } else {
                launchers.withoutIcons.push(launcher);
            }
        }, this);

        return launchers;
    },

    /**
     * Show dropdown
     *
     * @param {Event} e
     * @protected
     */
    _showDropdown: function(e) {
        this.showDropdown();
        e.preventDefault();
        e.stopPropagation();
    },

    showDropdown() {
        this.fillLauncherList();
        if (!this.$('[data-toggle="dropdown"]').parent().hasClass('show')) {
            this.$('[data-toggle="dropdown"]').dropdown('toggle');
        }
        this.model.set('isDropdownActions', this.isDropdownActions);
    },

    /**
     * Hide dropdown
     *
     * @param {Event} e
     * @protected
     */
    _hideDropdown: function(e) {
        this.hideDropdown();
        e.stopPropagation();
    },

    hideDropdown() {
        if (this.$('[data-toggle="dropdown"]').parent().hasClass('show')) {
            this.$('[data-toggle="dropdown"]').dropdown('toggle');
        }
    },

    onKeydown: function(event) {
        // close dropdown on ESC key
        if (event.which === 27) {
            this._hideDropdown(event);
        }
    }
});

export default ActionCell;
