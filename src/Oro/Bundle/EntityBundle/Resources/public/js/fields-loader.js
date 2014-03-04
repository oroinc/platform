/*global define*/
/*jslint nomen: true*/
define(['jquery', 'routing', 'orotranslation/js/translator', 'oroui/js/messenger', 'oroui/js/app', 'jquery-ui'
    ], function ($, routing, __, messenger, app) {
    'use strict';

    /**
     * Widget responsible for loading fields of selected entity
     */
    $.widget('oroentity.fieldsLoader', {
        options: {
            router: 'oro_api_get_entity_fields',
            routingParams: {
                'with-relations': 1,
                'with-entity-details': 1,
                'deep-level': 1
            },
            // supports 'oroui/js/modal' confirmation dialog
            confirm: null,
            requireConfirm: function () { return true; }
        },

        _create: function () {
            this.setFieldsData(this.element.data('fields') || []);

            this._on({
                change: this._onChange
            });
        },

        generateURL: function (entityName) {
            var opts = $.extend({}, this.options.routingParams, {entityName: entityName.replace(/\\/g, "_")});
            return routing.generate(this.options.router, opts);
        },

        _onChange: function (e) {
            var oldVal, confirm = this.options.confirm;
            if (confirm && this.options.requireConfirm()) {
                // @todo support also other kind of inputs than select2
                oldVal = (e.removed && e.removed.id) || null;
                this._confirm(confirm, e.val, oldVal);
            } else {
                this.loadFields();
            }
        },

        loadFields: function () {
            var entityName = this.element.val();
            $.ajax({
                url: this.generateURL(entityName),
                success: $.proxy(this._onLoaded, this),
                error: this._onError,
                beforeSend: $.proxy(this._trigger, this, 'start'),
                complete: $.proxy(this._trigger, this, 'complete')
            });
        },

        setFieldsData: function (data) {
            var fields = this._convertFields(data);
            this.element.data('fields', fields);
            this._trigger('update', null, [fields]);
        },

        _confirm: function (confirm, newVal, oldVal) {
            var $el = this.element,
                load = $.proxy(this.loadFields, this),
                revert = function () { $el.val(oldVal); };
            confirm.on('ok', load);
            confirm.on('cancel', revert);
            confirm.once('hidden', function () {
                confirm.off('ok', load);
                confirm.off('cancel', revert);
            });
            confirm.open();
        },

        _onLoaded: function (data) {
            this.setFieldsData(data);
        },

        _onError: function (jqXHR) {
            var err = jqXHR.responseJSON,
                msg = __('Sorry, unexpected error was occurred');
            if (app.debug) {
                if (err.message) {
                    msg += ': ' + err.message;
                } else if (err.errors && $.isArray(err.errors)) {
                    msg += ': ' + err.errors.join();
                } else if ($.type(err) === 'string') {
                    msg += ': ' + err;
                }
            }
            messenger.notificationFlashMessage('error', msg);
        },

        /**
         * Converts data in proper array of fields hierarchy
         *
         * @param {Array} data
         * @param {Object?} parent
         * @returns {Array}
         * @private
         */
        _convertFields: function (data, parent) {
            // @todo data converter from 'entity-field-*-util' should be implemented here
            return data;
        }
    });
});
