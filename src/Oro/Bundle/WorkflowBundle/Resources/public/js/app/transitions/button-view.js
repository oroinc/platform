define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var DialogWidget = require('oro/dialog-widget');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var TransitionModel = require('oroworkflow/js/app/transitions/model');


    /**
     * View action of transition button which handles execution of transition.
     */
    var TransitionButtonView = BaseView.extend({
        /** @property {Object} */
        options: {
            transitionData: {},
            transitionType: TransitionModel,
            redirectOnSuccess: false,
            loadingMaskEnabled: true,
            dialogWidgetOptions: {
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    width: 475,
                    autoResize: true
                }
            }
        },

        /** @property {Object} */
        events: {
            'click': 'onClick'
        },

        /** @property {Boolean} */
        inProgress: false,

        /** @property {TransitionModel} */
        model: null,

        /**
         * @constructor
         * @param {{
         *  transitionData: function,
         *  transitionType: function,
         *  redirectOnSuccess: Boolean,
         *  loadingMaskEnabled: Boolean,
         *  renderOptions: Object
         *  dialogWidgetOptions: Object
         * }} options
         */
        initialize: function(options) {
            TransitionButtonView.__super__.initialize.apply(this, arguments);

            options = options || {};

            if (options.renderOptions) {
                _.defaults(options.renderOptions, this.options.renderOptions);
            }

            if (options.dialogWidgetOptions) {
                if (options.dialogWidgetOptions.dialogOptions) {
                    _.defaults(options.dialogWidgetOptions.dialogOptions, this.options.dialogWidgetOptions.dialogOptions);
                }
                _.defaults(options.dialogWidgetOptions, this.options.dialogWidgetOptions);
            }

            this.options = _.defaults(options, this.options);
            this.ensureModel();
            this.render();
        },

        ensureModel: function() {
            if (!this.model) {
                this.model = new this.options.transitionType(this.options.transitionData);
            }
        },

        render: function () {
            var frontendOptions = this.model.get('frontendOptions');

            if (frontendOptions.class) {
                this.$el.addClass(frontendOptions.class);
            }

            if (!this.model.get('isAllowed')) {
                this.$el.attr('disabled', 'disabled');
            }

            if (!this.$el.attr('title')) {
                this.$el.attr('title', this.model.get('label'));
            }

            return this;
        },

        /**
         * Handle transition execution.
         */
        onClick: function () {
            if (this.inProgress) {
                return;
            }

            if (!this.model.get('hasForm')) {
                this.executeTransition();
            } else {
                this.showTransitionDialog();
            }
        },

        /**
         * Execute transition.
         */
        executeTransition: function() {
            this.inProgress = true;

            this.showLoading();

            var self = this;
            var url = this.model.get('transitionUrl');

            $.getJSON(url)
                .done(function(response) {
                    self.hideLoading();
                    self.inProgress = false;

                    self.$el.one('transition_success', _.bind(self.onSuccess, self));
                    self.$el.trigger('transition_success', [response, self.model]);
                })
                .fail(function(jqxhr, textStatus, error) {
                    self.hideLoading();
                    self.inProgress = false;

                    self.$el.one('transition_failure', _.bind(self.onFailure, self));
                    self.$el.trigger('transition_failure', [jqxhr, textStatus, error, self.model]);
                });
        },

        /**
         * Show transition dialog.         *
         */
        showTransitionDialog: function() {
            this.inProgress = true;

            var self = this;
            var transitionDialogWidget = this.createTransitionDialogWidget();

            transitionDialogWidget
                .on('widgetRemove', function() {
                    self.inProgress = false;
                })
                .on('formSave', function() {
                    transitionDialogWidget.remove();
                    self.inProgress = false;
                    self.executeTransition();
                });

            transitionDialogWidget.render();
        },

        /**
         * @return {DialogWidget}
         */
        createTransitionDialogWidget: function() {
            var frontendOptions = this.model.get('frontendOptions') || {};

            var dialogWidgetOptions = _.extend({
                title: this.model.get('label'),
                url: this.model.get('dialogUrl'),
                loadingMaskEnabled: this.options.loadingMaskEnabled
            }, this.options.dialogWidgetOptions);

            if (frontendOptions.dialogOptions) {
                dialogWidgetOptions.dialogOptions = _.extend(dialogWidgetOptions.dialogOptions, frontendOptions.dialogOptions);
            }

            return new DialogWidget(dialogWidgetOptions);
        },

        /**
         * Handles success transition execution, does redirect or reload if option "redirectOnSuccess" is TRUE
         *
         * @param {Event} e
         * @param {{
         *  workflowItem: {
         *      result: {
         *          redirectUrl: String
         *      }
         *  }
         * }} response
         */
        onSuccess: function(e, response) {
            if (
                response.workflowItem &&
                response.workflowItem.result &&
                response.workflowItem.result.redirectUrl
            ) {
                // Handle redirect if redirectUrl was returend in response */
                this.doRedirect(response.workflowItem.result.redirectUrl);
            } else {
                // Otherwise reload the page
                this.doReload();
            }
        },

        /**
         * Redirect page if "redirectOnSuccess" is TRUE
         *
         * @param {String} redirectUrl
         */
        doRedirect: function(redirectUrl) {
            if (this.options.redirectOnSuccess) {
                mediator.execute('redirectTo', {url: redirectUrl});
            }
        },

        /**
         * Reload page if "redirectOnSuccess" is TRUE
         */
        doReload: function() {
            if (this.options.redirectOnSuccess) {
                mediator.execute('refreshPage');
            }
        },

        /**
         * Shows flash message with error if transition was failed
         */
        onFailure: function() {
            messenger.notificationFlashMessage('error', __('Could not perform transition'));
        },

        /**
         * Show loading if "loadingMaskEnabled" is TRUE
         */
        showLoading: function() {
            if (this.options.loadingMaskEnabled) {
                mediator.execute('showLoading');
            }
        },

        /**
         * Hide loading if "loadingMaskEnabled" is TRUE
         */
        hideLoading: function() {
            if (this.options.loadingMaskEnabled) {
                mediator.execute('hideLoading');
            }
        }
    });

    return TransitionButtonView;
});
