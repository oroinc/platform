define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const BlockWidget = require('oro/block-widget');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');
    const dashboardItemTpl = require('tpl-loader!orodashboard/templates/widget/dashboard-item.html');
    const $ = Backbone.$;

    /**
     * @export  orodashboard/js/widget/dashboard-item
     * @class   orodashboard.DashboardItemWidget
     * @extends oro.BlockWidget
     */
    const DashboardItemWidget = BlockWidget.extend({
        /**
         * Widget events
         *
         * @property {Object}
         */
        widgetEvents: {
            'show.bs.collapse': 'onExpand',
            'hide.bs.collapse': 'onCollapse',
            'click [data-remove-action]'(event) {
                event.preventDefault();
                this.onRemoveFromDashboard();
            },
            'click [data-configure-action]'(event) {
                event.preventDefault();
                this.onConfigure();
            }
        },

        /**
         * @property {Object}
         */
        state: {
            id: 0,
            expanded: true,
            layoutPosition: [0, 0]
        },

        /**
         * Widget options
         *
         * @property {Object}
         */
        options: _.extend({}, BlockWidget.prototype.options, {
            type: 'dashboard-item',
            actionsContainer: '.widget-actions-container',
            contentContainer: '.row-fluid',
            contentClasses: [],
            allowEdit: false,
            template: dashboardItemTpl,
            configurationDialogOptions: {}
        }),

        /**
         * @inheritdoc
         */
        constructor: function DashboardItemWidget(options) {
            DashboardItemWidget.__super__.constructor.call(this, options);
        },

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize(options) {
            this.options = Object.assign({}, this.options, options || {});
            const {widgetName, allowEdit, state, showConfig} = this.options;
            this.options.templateParams = Object.assign({}, this.options.templateParams, {
                allowEdit,
                expanded: state.expanded,
                showConfig,
                headerClass: widgetName ? widgetName.split('_').join('-') + '-widget-header' : ''
            });

            if (!this.options.title) {
                this.options.title = this.$el.data('widget-title');
            }

            DashboardItemWidget.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render() {
            if (this.isCollapsed()) {
                this.widgetContentContainer.attr('data-layout', 'separate');
                this.listenToOnce(this, 'expand', () => {
                    this.widgetContentContainer.removeAttr('data-layout');
                    this.render();
                });
            }
            return DashboardItemWidget.__super__.render.call(this);
        },

        /**
         * Initialize widget
         *
         * @param {Object} options
         */
        initializeWidget(options) {
            this._initState(options);
            DashboardItemWidget.__super__.initializeWidget.call(this, options);
        },

        _afterLayoutInit: function() {
            this.$el.removeClass('invisible');
            DashboardItemWidget.__super__._afterLayoutInit.call(this);
        },

        /**
         * Initialize state
         *
         * @param {Object} options
         * @private
         */
        _initState(options) {
            if (options.state) {
                this.state = _.extend({}, this.state, options.state);
            }

            if (this.state.layoutPosition instanceof Array) {
                this.state.layoutPosition = this.state.layoutPosition.map(value => parseInt(value));
            } else {
                this.state.layoutPosition = [0, 0];
            }

            if (!this.state.id) {
                throw new Error('Dashboard widget id should be defined.');
            }
        },

        /**
         * Handles bootstrap collapse hide event
         */
        onCollapse() {
            this.state.expanded = false;

            this.trigger('collapse', this.$el, this);
            mediator.trigger('widget:dashboard:collapse:' + this.getWid(), this.$el, this);
        },

        /**
         * Handles bootstrap collapse show event
         */
        onExpand() {
            this.state.expanded = true;

            this.trigger('expand', this.$el, this);
            mediator.trigger('widget:dashboard:expand:' + this.getWid(), this.$el, this);

            const $chart = this.$el.find('.chart');
            if ($chart.length > 0) {
                $chart.trigger('update');
            }
        },

        /**
         * Is collapsed
         *
         * @returns {Boolean}
         */
        isCollapsed() {
            return !this.state.expanded;
        },

        /**
         * Trigger remove action
         */
        onRemoveFromDashboard() {
            const confirm = new DeleteConfirmation({
                content: __('oro.dashboard.widget.delete_confirmation')
            });

            confirm.on('ok', () => {
                this.trigger('removeFromDashboard', this.$el, this);
                mediator.trigger('widget:dashboard:removeFromDashboard:' + this.getWid(), this.$el, this);
            });
            confirm.open();
        },

        /**
         * Trigger configure action
         */
        onConfigure() {
            this.trigger('configure', this.$el, this);
            mediator.trigger('widget:dashboard:configure:' + this.getWid(), this.$el, this);
        },

        /**
         * Handle loaded content.
         *
         * @param {String} content
         * @private
         */
        _onContentLoad(content) {
            const title = $(content).data('widget-title');

            if (title) {
                this.setTitle(title);
            }

            DashboardItemWidget.__super__._onContentLoad.call(this, content);
        }
    });

    return DashboardItemWidget;
});
