define([
    'jquery',
    'underscore',
    'backgrid',
    'module'
], function($, _, Backgrid, module) {
    'use strict';

    var config = module.config();
    config = _.extend({
        showCloseButton: false
    }, config);

    var ActionCell;

    /**
     * Cell for grid, contains actions
     *
     * @export  oro/datagrid/cell/action-cell
     * @class   oro.datagrid.cell.ActionCell
     * @extends Backgrid.Cell
     */
    ActionCell = Backgrid.Cell.extend({

        /** @property */
        className: 'action-cell',

        /** @property {Array} */
        actions: undefined,

        /** @property Integer */
        actionsHideCount: 3,

        /** @property {Array} */
        launchers: undefined,

        /** @property Boolean */
        showCloseButton: config.showCloseButton,

        /** @property */
        cellMarkup: '<div class="more-bar-holder action-row"></div>',

        /** @property */
        dropdownContainer: '<div class="dropdown"></div>',

        /** @property */
        dropdownList: '<div class="dropdown-menu dropdown-menu__action-cell" ' +
            'data-options="{&quot;container&quot;: true, &quot;align&quot;: &quot;right&quot;}">' +
        '</div>',

        /** @property */
        closeButton: '<a data-toggle="dropdown" class="dropdown-toggle" href="javascript:void(0);">...</a>',

        /** @property */
        simpleLaunchersListTemplate: _.template(
            '<% if (withIcons) { %>' +
                '<ul class="nav nav-pills icons-holder launchers-list"></ul>' +
            '<% } else { %>' +
                '<ul class="unstyled launchers-list"></ul>' +
            '<% } %>'
        ),

        /** @property */
        launchersContainerSelector: '.more-bar-holder',

        /** @property */
        launchersListSelector: '.launchers-list',

        /** @property */
        closeButtonTemplate: _.template(
            '<li class="dropdown-close"><i class="fa-close hide-text">' + _.__('Close') + '</i></li>'
        ),

        /** @property */
        launchersListTemplate: _.template(
            '<% if (withIcons) { %>' +
                '<li><ul class="nav nav-pills icons-holder launchers-list"></ul></li>' +
            '<% } else { %>' +
                '<li class="well-small"><ul class="unstyled launchers-list"></ul></li>' +
            '<% } %>'
        ),

        /** @property */
        launcherItemTemplate: _.template(
            '<li class="launcher-item <%= className %>"></li>'
        ),

        /** @property */
        events: {
            'click': '_showDropdown',
            'mouseover .dropdown-toggle': '_showDropdown',
            'mouseleave .dropdown-menu, .dropdown-menu__placeholder': '_hideDropdown',
            'click .dropdown-close .fa-close': '_hideDropdown'
        },

        /**
         * Initialize cell actions and launchers
         */
        initialize: function(options) {
            var opts = options || {};
            this.subviews = [];

            if (!_.isUndefined(opts.actionsHideCount)) {
                this.actionsHideCount = opts.actionsHideCount;
            }

            if (!_.isUndefined(opts.actionsHideCount)) {
                this.actionsHideCount = opts.actionsHideCount;
            }

            if (!_.isUndefined(opts.themeOptions.actionsHideCount)) {
                this.actionsHideCount = opts.themeOptions.actionsHideCount;
            }

            if (!_.isUndefined(opts.actionsDropdown)) {
                this.actionsDropdown = opts.actionsDropdown;
            }

            this.launcherMode = _.isObject(opts.themeOptions.launcherOptions) ?
                                    opts.themeOptions.launcherOptions.launcherMode : false;

            ActionCell.__super__.initialize.apply(this, arguments);
            this.actions = this.createActions();
            _.each(this.actions, function(action) {
                this.listenTo(action, 'preExecute', this.onActionRun);
            }, this);

            this.subviews.push.apply(this.subviews, this.actions);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.actions;
            delete this.column;
            delete this.launcherMode;
            this.$('.dropdown-toggle').dropdown('destroy');
            ActionCell.__super__.dispose.apply(this, arguments);
        },

        /**
         * Handle action run
         *
         * @param {oro.datagrid.action.AbstractAction} action
         */
        onActionRun: function(action) {
            this.$('.dropdown.open .dropdown-toggle').trigger('tohide.bs.dropdown');
        },

        /**
         * Creates actions
         *
         * @return {Array}
         */
        createActions: function() {
            var result = [];
            var actions = this.column.get('actions');
            var config = this.model.get('action_configuration') || {};

            _.each(actions, function(action, name) {
                // filter available actions for current row
                if (!config || config[name] !== false) {
                    result.push(this.createAction(action, config[name] || {}));
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
            return _.map(this.actions, function(action) {
                return action.createLauncher({launcherMode: this.launcherMode});
            }, this);
        },

        /**
         * Render cell with actions
         */
        render: function() {
            // don't render anything if list of launchers is empty
            if (_.isEmpty(this.actions)) {
                this.$el.empty();

                return this;
            }

            var wrapDropdown = _.bind(this.wrapDropdown, this);

            this.$el.css({visibility: 'hidden'});

            this.launchersListTemplate = this.simpleLaunchersListTemplate;

            this.$el.html(this.cellMarkup);
            this.fillLauncherList();

            if (_.has(this, 'actionsDropdown')) {
                if (this.actionsDropdown) {
                    wrapDropdown();
                }

                this.$el.css({visibility: ''});
            } else {
                setTimeout(_.bind(function() {
                    var $list = this.$(this.launchersListSelector);
                    var listHeight = $list.height();
                    var testListItemHeight = $list.find('.launcher-item').height() * 1.5;

                    if (listHeight > testListItemHeight) {
                        wrapDropdown();
                    }

                    this.$el.css({visibility: ''});
                }, this), 0);
            }

            return this;
        },

        wrapDropdown: function() {
            var $dropDownContainer;
            var $dropDownPopup;
            var $list = this.$(this.launchersListSelector);

            $dropDownPopup = $list.wrap(this.dropdownList).parent();
            $dropDownContainer = $dropDownPopup.wrap(this.dropdownContainer).parent();
            $dropDownContainer.append(this.closeButton);
        },

        fillLauncherList: function() {
            if (!this.isLauncherListFilled) {
                this.isLauncherListFilled = true;

                var launcherList = this.createLaunchers();

                var launchers = this.getLaunchersByIcons(launcherList);
                var $listsContainer = this.$(this.launchersContainerSelector);

                if (this.showCloseButton && launcherList.length >= this.actionsHideCount) {
                    $listsContainer.append(this.closeButtonTemplate());
                }

                if (launchers.withIcons.length) {
                    this.renderLaunchersList(launchers.withIcons, {withIcons: true})
                        .appendTo($listsContainer);
                }

                if (launchers.withIcons.length && launchers.withoutIcons.length) {
                    $listsContainer.append('<li class="divider"></li>');
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
            var result = $(this.launchersListTemplate(params));
            var $launchersList = result.filter('.launchers-list').length ? result : $('.launchers-list', result);
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
            params = _.extend(params || {}, {className: launcher.launcherMode || ''});
            var result = $(this.launcherItemTemplate(params));
            var $launcherItem = result.filter('.launcher-item').length ? result : $('.launcher-item', result);
            $launcherItem.append(launcher.render().$el);
            return result;
        },

        /**
         * Get separate object of launchers arrays: with icons (key `withIcons`) and without icons (key `withoutIcons`).
         *
         * @return {Object}
         * @protected
         */
        getLaunchersByIcons: function(launcherList) {
            var launchers = {
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
            this.fillLauncherList();
            if (!this.$('.dropdown-toggle').parent().hasClass('open')) {
                this.$('.dropdown-toggle').dropdown('toggle');
            }
            e.stopPropagation();
        },

        /**
         * Hide dropdown
         *
         * @param {Event} e
         * @protected
         */
        _hideDropdown: function(e) {
            if (this.$('.dropdown-toggle').parent().hasClass('open')) {
                this.$('.dropdown-toggle').dropdown('toggle');
            }
            e.stopPropagation();
        }
    });

    return ActionCell;
});
