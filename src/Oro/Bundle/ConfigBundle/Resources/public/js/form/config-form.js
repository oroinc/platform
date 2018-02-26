define(function(require) {
    'use strict';

    var ConfigForm;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var Modal = require('oroui/js/modal');
    var DefaultFieldValueView = require('oroform/js/app/views/default-field-value-view');

    ConfigForm = DefaultFieldValueView.extend({

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
            'submit': 'submitHandler'
        },

        /**
         * @inheritDoc
         */
        constructor: function ConfigForm() {
            ConfigForm.__super__.constructor.apply(this, arguments);
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
                    _.bind(this._onNeedsReloadChange, this)
                );
            }
            window.view = this;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('change', 'input[data-needs-page-reload]');

            ConfigForm.__super__.dispose.apply(this, arguments);
        },

        _onNeedsReloadChange: function(e) {
            var $input = $(e.target);
            var name = $input.attr('name');

            if (this.changedValues.hasOwnProperty(name)) {
                delete this.changedValues[name];
            } else {
                this.changedValues[name] = $input.val();
            }

            this.options.pageReload = !_.isEmpty(this.changedValues);
        },

        /**
         * Resets form and default value checkboxes.
         *
         * @param event
         */
        resetHandler: function(event) {
            var $checkboxes = this.$el.find('.parent-scope-checkbox input');
            var confirm = new Modal({
                title: __('Confirmation'),
                okText: __('OK'),
                cancelText: __('Cancel'),
                content: __('Settings will be restored to saved values. Please confirm you want to continue.'),
                className: 'modal modal-primary'
            });

            confirm.on('ok', _.bind(function() {
                this.$el.get(0).reset();
                this.$el.find('.select2').each(function(key, elem) {
                    $(elem).inputWidget('val', null, true);
                });
                this.$el.find('.removeRow').each(function() {
                    var $row = $(this).closest('*[data-content]');
                    // non-persisted options have a simple number for data-content
                    if (_.isNumber($row.data('content'))) {
                        $row.trigger('content:remove').remove();
                    }
                });
                $checkboxes
                    .prop('checked', true)
                    .attr('checked', true);

                this.$el.find(':input').change();
            }, this));

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
            if (this.options.pageReload) {
                mediator.off('config-form:init', this.onInitAfterSubmit)
                    .once('config-form:init', this.onInitAfterSubmit);
            }
        },

        onInitAfterSubmit: function(options) {
            if (options.isFormValid) {
                messenger.notificationMessage('info', __('Please wait until page will be reloaded...'));
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
