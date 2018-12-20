define(function(require) {
    'use strict';

    /**
     * Displays actions dropdown
     *
     * @param {Object}   options - options container
     * @param {Object}   options.model - model
     * @param {Object}   options.datagrid - datagrid link
     * @param {Object}   options.actions - actions array
     * @param {Object}   options.actions_configuration - additional actions configuration
     */
    var ActionsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionsView = BaseView.extend({

        /** @property {Array} */
        actions: undefined,

        /** @property Integer */
        actionsHideCount: 3,

        /** @property {Array} */
        launchers: undefined,

        /** @property Boolean */
        showCloseButton: false,

        /** @property */
        baseMarkup: _.template(
            '<div class="more-bar-holder">' +
                '<div class="dropleft">' +
                    '<a class="dropdown-toggle" href="#" role="button" id="<%- togglerId %>" data-toggle="dropdown" ' +
                        'aria-haspopup="true" aria-expanded="false" aria-label="<%- label %>">' +
                        '<span class="icon fa-ellipsis-h" aria-hidden="true"></span>' +
                    '</a>' +
                    '<ul class="dropdown-menu dropdown-menu__action-cell launchers-dropdown-menu" ' +
                        'aria-labelledby="<%- togglerId %>"></ul>' +
                '</div>' +
            '</div>'
        ),

        /** @property */
        simpleBaseMarkup: _.template('<div class="more-bar-holder action-row"></div>'),

        /** @property */
        closeButtonTemplate: _.template(
            '<li class="dropdown-close"><i class="fa-close hide-text">' + __('Close') + '</i></li>'
        ),

        /** @property */
        launchersContainerSelector: '.launchers-dropdown-menu',

        /** @property */
        launchersListTemplate: _.template(
            '<% if (withIcons) { %>' +
                '<li><ul class="launchers-list"></ul></li>' +
            '<% } else { %>' +
                '<li class="well-small"><ul class="unstyled launchers-list"></ul></li>' +
            '<% } %>'
        ),

        /** @property */
        simpleLaunchersListTemplate: _.template(
            '<% if (withIcons) { %>' +
                '<ul class="launchers-list"></ul>' +
            '<% } else { %>' +
                '<ul class="unstyled launchers-list"></ul>' +
            '<% } %>'
        ),

        /** @property */
        launcherItemTemplate: _.template(
            '<li class="launcher-item"></li>'
        ),

        /** @property */
        events: {
            'click': '_showDropdown',
            'mouseover .dropdown-toggle': '_showDropdown',
            'mouseleave .dropleft.show': '_hideDropdown',
            'click .dropdown-close .fa-close': '_hideDropdown'
        },

        /**
         * @inheritDoc
         */
        constructor: function ActionsView() {
            ActionsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initialize cell actions and launchers
         */
        initialize: function(options) {
            var opts = options || {};
            this.subviews = [];

            if (!_.isEmpty(opts.actionsHideCount)) {
                this.actionsHideCount = opts.actionsHideCount;
            }

            this.showCloseButton = opts.showCloseButton;

            ActionsView.__super__.initialize.apply(this, arguments);
            this.actions = this.createActions(opts);
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
            ActionsView.__super__.dispose.apply(this, arguments);
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
        createActions: function(opts) {
            var result = [];
            var actions = opts.actions;
            var config = opts.actionConfiguration;

            _.each(actions, function(action, name) {
                // filter available actions for current row
                if (!config || config[name] !== false) {
                    result.push(this.createAction(action, opts));
                }
            }, this);

            return result;
        },

        isEmpty: function() {
            return !this.actions.length;
        },

        /**
         * Creates action
         *
         * @param {Function} Action
         * @protected
         */
        createAction: function(Action, opts) {
            return new Action(_.extend({
                model: opts.model,
                datagrid: opts.datagrid
            }, opts.actionOptions));
        },

        /**
         * Creates actions launchers
         *
         * @protected
         */
        createLaunchers: function() {
            return _.map(this.actions, function(action) {
                return action.createLauncher({});
            }, this);
        },

        /**
         * Render cell with actions
         */
        render: function() {
            var isSimplifiedMarkupApplied = false;
            // don't render anything if list of launchers is empty
            if (_.isEmpty(this.actions)) {
                this.$el.empty();

                return this;
            }

            if (this.actions.length < this.actionsHideCount) {
                isSimplifiedMarkupApplied = true;
                this.baseMarkup = this.simpleBaseMarkup;
                this.launchersListTemplate = this.simpleLaunchersListTemplate;
                this.launchersContainerSelector = '.more-bar-holder';
            }

            this.$el.html(this.baseMarkup(this.getTemplateData()));
            this.isLauncherListFilled = false;

            if (isSimplifiedMarkupApplied) {
                this.fillLauncherList();
            }

            return this;
        },

        getTemplateData: function() {
            return {
                togglerId: 'actions-view-dropdown-' + this.cid,
                label: __('oro.datagrid.card_actions.label')
            };
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
            params = params || {};
            var result = $(this.launcherItemTemplate(params));
            var $launcherItem = result.filter('.launcher-item').length ? result : $('.launcher-item', result);
            $launcherItem.append(launcher.render().$el);
            var className = 'mode-' + launcher.launcherMode;
            $launcherItem.addClass(className);
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
            if (!this.$('[data-toggle="dropdown"]').parent().hasClass('show')) {
                this.$('[data-toggle="dropdown"]').dropdown('toggle');
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
            if (this.$('[data-toggle="dropdown"]').parent().hasClass('show')) {
                this.$('[data-toggle="dropdown"]').dropdown('toggle');
            }
            e.stopPropagation();
        }
    });

    return ActionsView;
});
