define(['jquery', 'backbone', 'underscore', 'oro/translator', 'oro/delete-confirmation'],
    function ($, Backbone, _, __, DeleteConfirmation) {
        "use strict";

        /**
         * @export  oro/integration/channel-view
         * @class   oro.integration.channelView
         * @extends Backbone.View
         */
        return Backbone.View.extend({
            /**
             * @param options Object
             */
            initialize: function (options) {
                if (_.isUndefined(options.transportTypeSelector) || _.isUndefined(options.typeSelector)) {
                    new TypeError('Missing required options');
                }

                this.processSelectorState();
                $(options.typeSelector).on('change', _.bind(this.changeHandler, this));
                $(options.transportTypeSelector).on('change', _.bind(this.changeHandler, this));
                this.memoizeValue(options.typeSelector);
                this.memoizeValue(options.transportTypeSelector);
            },

            /**
             * Hide transport type select element in case when only one type exists
             */
            processSelectorState: function () {
                var $el = $(this.options.transportTypeSelector);

                if ($el.find('option').length < 2) {
                    $el.parents('.control-group').hide();
                }
            },

            changeHandler: function (e) {
                var $el = $(e.currentTarget);
                if ($el.data('cancelled') !== true) {
                    var prevVal = $el.data('current');
                    if (!this.isEmpty()) {
                        var confirm = new DeleteConfirmation({
                            title: __('Change Type'),
                            okText: __('Yes, I Agree'),
                            content: __('Are you sure you want to change type(settings related to previous type will be cleared)?')
                        });
                        confirm.on('ok', _.bind(function () {
                            this.processChange();
                            this.memoizeValue($el);
                        }, this));
                        confirm.on('cancel', _.bind(function () {
                            $el.data('cancelled', true);
                            $el.val(prevVal);
                            $el.trigger('change');
                            this.memoizeValue($el);
                        }, this));
                        confirm.open();
                    } else {
                        this.processChange();
                        this.memoizeValue($el);
                    }
                } else {
                    $el.data('cancelled', false);
                }
            },

            /**
             * Update form via ajax
             * Render dynamic fields
             */
            processChange: function () {
                var $form = $(this.options.typeSelector).parents('form'),
                    data = $form.serialize(),
                    url = $form.attr('action');

                $.post(url, data, function (res) {
                    var content = $(res).find('#container');
                    if (content.length) {
                        $('#container').replaceWith(content);
                    }
                });
            },

            /**
             * Check whenever form fields are empty
             *
             * @returns {boolean}
             */
            isEmpty: function () {
                var fields = $(this.options.typeSelector).parents('form').find('input[type="text"]');

                fields = fields.filter(function () {
                    return this.value != '';
                });

                return !fields.length;
            },

            /**
             * Remember current value in case if in future we will need to undo change
             *
             * @param el select element
             */
            memoizeValue: function (el) {
                var $el = $(el);
                $el.data('current', $el.val());
            }
        });
    });
