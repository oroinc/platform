define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/delete-confirmation'
], function($, Backbone, _, __, mediator, DeleteConfirmation) {
    'use strict';

    /**
     * @export  orointegration/js/channel-view
     * @class   orointegration.channelView
     * @extends Backbone.View
     */
    const ChanelView = Backbone.View.extend({
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
         * @inheritdoc
         */
        constructor: function ChanelView(options) {
            ChanelView.__super__.constructor.call(this, options);
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            const requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }

            for (const fieldSet in options.fieldsSets) {
                if (fieldSet in this.fieldsSets) {
                    this.fieldsSets[fieldSet] = _.union(this.fieldsSets[fieldSet], options.fieldsSets[fieldSet]);
                } else {
                    this.fieldsSets[fieldSet] = options.fieldsSets[fieldSet];
                }
            }

            this.processSelectorState();
            $(options.typeSelector).on('change', this.changeHandler.bind(this));
            $(options.transportTypeSelector).on('change', this.changeHandler.bind(this));
            this.memoizeValue(options.typeSelector);
            this.memoizeValue(options.transportTypeSelector);
        },

        /**
         * Hide transport type select element in case when only one type exists
         */
        processSelectorState: function() {
            const $el = $(this.options.transportTypeSelector);

            if ($el.find('option').length < 2) {
                $el.parents('.control-group:first').hide();
            }
        },

        /**
         * Check whenever form change and shows confirmation
         * @param {$.Event} e
         */
        changeHandler: function(e) {
            const $el = $(e.currentTarget);
            if ($el.data('cancelled') !== true) {
                const prevVal = $el.data('current');
                if (!this.isEmpty()) {
                    const confirm = new DeleteConfirmation({
                        title: __('oro.integration.change_type'),
                        okText: __('Yes'),
                        content: __('oro.integration.submit')
                    });
                    confirm.on('ok', () => {
                        this.processChange($el);
                    });
                    confirm.on('cancel', () => {
                        $el.data('cancelled', true).val(prevVal).trigger('change');
                        this.memoizeValue($el);
                    });
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

            const $form = $(this.options.formSelector);
            let data = $form.serializeArray();
            const url = $form.attr('action');
            const fieldsSet = $el.is(this.options.typeSelector) ? this.fieldsSets.type : this.fieldsSets.transportType;

            data = _.filter(data, function(field) {
                return _.indexOf(fieldsSet, field.name) !== -1;
            });
            data.push({name: this.UPDATE_MARKER, value: $el.attr('name')});

            const event = {formEl: $form, data: data, reloadManually: true};
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
            let fields = $(this.options.typeSelector).parents('form').find('input[type="text"]:not([name$="[name]"])');

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
            const $el = $(el);
            $el.data('current', $el.val());
        }
    });

    return ChanelView;
});
