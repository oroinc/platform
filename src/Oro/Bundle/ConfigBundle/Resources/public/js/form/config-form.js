define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const Modal = require('oroui/js/modal');
    const DefaultFieldValueView = require('oroform/js/app/views/default-field-value-view');

    const ConfigForm = DefaultFieldValueView.extend({

        /**
         * @param {Object} Where key is input name and value is changed value
         */
        changedValues: {},

        defaults: {
            pageReload: false,
            isFormValid: true
        },

        events: {
            'click :input[type=reset]': 'resetHandler',
            'submit': 'submitHandler',
            'change .parent-scope-checkbox :input[type=checkbox]': 'onDefaultCheckboxStateChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function ConfigForm(options) {
            ConfigForm.__super__.constructor.call(this, options);
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.defaults, this.options);
            mediator.trigger('config-form:init', this.options);
            if (!this.options.pageReload) {
                this.$el.on(
                    'change',
                    'input[data-needs-page-reload]',
                    this._onNeedsReloadChange.bind(this)
                );
            }
            window.view = this;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('change', 'input[data-needs-page-reload]');

            ConfigForm.__super__.dispose.call(this);
        },

        _onNeedsReloadChange: function(e) {
            const $input = $(e.target);
            const name = $input.attr('name');

            if (this.changedValues.hasOwnProperty(name)) {
                delete this.changedValues[name];
            } else {
                this.changedValues[name] = $input.val();
            }

            this.options.pageReload = !_.isEmpty(this.changedValues);
        },

        removeValidationErrors: function($field) {
            const $container = $field.closest('.controls');
            $container
                .removeClass('validation-error')
                .find('.error')
                .removeClass('error');
            $container.find('.validation-failed').remove();
        },

        onDefaultCheckboxStateChange: function(e) {
            const $checkbox = $(e.target);
            if ($checkbox.is(':checked')) {
                this.removeValidationErrors($checkbox);
            }
        },

        /**
         * Resets form and default value checkboxes.
         *
         * @param event
         */
        resetHandler: function(event) {
            const $checkboxes = this.$el.find('.parent-scope-checkbox input');
            const confirm = new Modal({
                title: __('Confirmation'),
                okText: __('OK'),
                cancelText: __('Cancel'),
                content: __('Settings will be restored to saved values. Please confirm you want to continue.'),
                className: 'modal modal-primary'
            });

            const self = this;
            confirm.on('ok', () => {
                this.$el.get(0).reset();
                this.$el.find('.select2').each(function(key, elem) {
                    $(elem).inputWidget('val', null, true);
                });
                this.$el.find('.removeRow').each(function() {
                    const $row = $(this).closest('*[data-content]');
                    // non-persisted options have a simple number for data-content
                    if (_.isNumber($row.data('content'))) {
                        $row.trigger('content:remove').remove();
                    }
                });
                $checkboxes
                    .prop('checked', true)
                    .attr('checked', true);

                this.$el.find(':input').change();

                this.$el.find(':input').each(function() {
                    const $field = $(this);
                    self.removeValidationErrors($field);
                });
            });

            confirm.open();

            event.preventDefault();
        },

        /**
         * Reloads page on form submit if reloadPage is set to true and response contains valid form
         *
         * We use mediator with event 'config-form:init', because we need to get option from new form that we'll
         * receive within response. Only new form contains validation result information.
         */
        submitHandler: function() {
            if (this.options.pageReload && mediator.execute('isPageStateChanged')) {
                mediator.off('config-form:init', this.onInitAfterSubmit)
                    .once('config-form:init', this.onInitAfterSubmit);
            }
        },

        onInitAfterSubmit: function(options) {
            if (options.isFormValid) {
                messenger.notificationMessage('info', __('Please wait for the page to reload...'));
                // force reload without hash navigation
                window.location.reload();

                this.once('page:afterChange', function() {
                    // Show loading until page is fully reloaded
                    this.execute('showLoading');
                });
            }
        }
    });

    return ConfigForm;
});
