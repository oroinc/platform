/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/messenger',
    'oroconfig/js/form/default',
    'oroui/js/modal',
    'jquery'
], function(_, Backbone, __, mediator, messenger, formDefault, Modal, $) {
    'use strict';

    /**
     * @extends Backbone.View
     */
    return Backbone.View.extend({

        defaults: {
            pageReload: false,
            isFormValid: true
        },

        events: {
            'click :input[type=reset]': 'resetHandler',
            'submit': 'submitHandler'
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.defaults, this.options);
            mediator.trigger('config-form:init', this.options);
            formDefault();
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
                    className: 'modal modal-primary',
                    okButtonClass: 'btn-primary btn-large'
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
                    .attr('checked', true)
                    .trigger('change');
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
                mediator.once('config-form:init', function(options) {
                    if (options.isFormValid) {
                        messenger.notificationMessage('info', __('Please wait until page will be reloaded...'));
                        // force reload without hash navigation
                        window.location.reload();

                        this.once('page:afterChange', function () {
                            // Show loading until page is fully reloaded
                            this.execute('showLoading');
                        });
                    }
                });
            }
        }
    });
});
