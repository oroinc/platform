define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/delete-confirmation'
], function($, Backbone, _, __, mediator, DeleteConfirmation) {
    'use strict';

    var ChanelView;

    /**
     * @export  orointegration/js/channel-view
     * @class   orointegration.channelView
     * @extends Backbone.View
     */
    ChanelView = Backbone.View.extend({
        /**
         * @const
         */
        UPDATE_MARKER: 'formUpdateMarker',

        /**
         * Array of fields that should be submitted for form update
         * Depends on what exact field changed
         */
        fieldsSets: {
            type: [],
            transportType: []
        },

        requiredOptions: ['transportTypeSelector', 'typeSelector', 'fieldsSets', 'formSelector'],

        /**
         * @inheritDoc
         */
        constructor: function ChanelView() {
            ChanelView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }

            _.extend(this.fieldsSets, options.fieldsSets);

            this.processSelectorState();
            $(options.typeSelector).on('change', _.bind(this.changeHandler, this));
            $(options.transportTypeSelector).on('change', _.bind(this.changeHandler, this));
            this.memoizeValue(options.typeSelector);
            this.memoizeValue(options.transportTypeSelector);
        },

        /**
         * Hide transport type select element in case when only one type exists
         */
        processSelectorState: function() {
            var $el = $(this.options.transportTypeSelector);

            if ($el.find('option').length < 2) {
                $el.parents('.control-group:first').hide();
            }
        },

        /**
         * Check whenever form change and shows confirmation
         * @param {$.Event} e
         */
        changeHandler: function(e) {
            var $el = $(e.currentTarget);
            if ($el.data('cancelled') !== true) {
                var prevVal = $el.data('current');
                if (!this.isEmpty()) {
                    var confirm = new DeleteConfirmation({
                        title: __('oro.integration.change_type'),
                        okText: __('Yes'),
                        content: __('oro.integration.submit')
                    });
                    confirm.on('ok', _.bind(function() {
                        this.processChange($el);
                    }, this));
                    confirm.on('cancel', _.bind(function() {
                        $el.data('cancelled', true).val(prevVal).trigger('change');
                        this.memoizeValue($el);
                    }, this));
                    confirm.open();
                } else {
                    this.processChange($el);
                }
            } else {
                $el.data('cancelled', false);
            }
        },

        /**
         * Updates form via ajax, renders dynamic fields
         *
         * @param {$.element} $el
         */
        processChange: function($el) {
            this.memoizeValue($el);

            var $form = $(this.options.formSelector);
            var data = $form.serializeArray();
            var url = $form.attr('action');
            var fieldsSet = $el.is(this.options.typeSelector) ? this.fieldsSets.type : this.fieldsSets.transportType;

            data = _.filter(data, function(field) {
                return _.indexOf(fieldsSet, field.name) !== -1;
            });
            data.push({name: this.UPDATE_MARKER, value: 1});

            var event = {formEl: $form, data: data, reloadManually: true};
            mediator.trigger('integrationFormReload:before', event);

            if (event.reloadManually) {
                mediator.execute('submitPage', {url: url, type: $form.attr('method'), data: $.param(data)});
            }
        },

        /**
         * Check whenever form fields are empty
         *
         * @returns {boolean}
         */
        isEmpty: function() {
            var fields = $(this.options.typeSelector).parents('form').find('input[type="text"]:not([name$="[name]"])');

            fields = fields.filter(function() {
                return this.value !== '';
            });

            return !fields.length;
        },

        /**
         * Remember current value in case if in future we will need to undo changes
         *
         * @param {HTMLSelectElement} el
         */
        memoizeValue: function(el) {
            var $el = $(el);
            $el.data('current', $el.val());
        }
    });

    return ChanelView;
});
