/*global define*/
/*jslint nomen: true*/
define(['jquery', 'routing', 'oro/translator', 'oro/messenger', 'jquery-ui'], function ($, routing, __, messenger) {
    'use strict';

    /**
     * Widget responsible for loading fields of selected entity
     */
    $.widget('oroentity.fieldsLoader', {
        options: {
            routing: {
                'with-relations': true,
                'with-entity-details': true,
                'deep-level': 1
            }
        },

        _create: function () {
            var data = this.element.data('fields');
            this.element.data('fields', this._convertFields(data));

            this._on({
                change: this.loadFields
            });
        },

        generateURL: function (entityName) {
            var opts = $.extend({}, this.options.routing, {entityName: entityName.replace(/\\/g, "_")});
            return routing.generate('oro_api_get_entity_fields', opts);
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

        _onLoaded: function (data) {
            this.element.data('fields', this._convertFields(data));
            this._trigger('success');
        },

        _onError: function () {
            var msg = __('Sorry, unexpected error was occurred');
            messenger.notificationFlashMessage('error', msg);
        },

        _convertFields: function (data) {
            var fields = data.map(function (field) {
                if (!field.related_entity_fields) {
                    return {
                        id: field.name,
                        text: field.label,

                        value: field.name,
                        type: field.type,
                        label: field.label
                    };
                }

                return {
                    id: field.name,
                    text: field.label,

                    children: field.related_entity_fields.map(function (child) {
                        return {
                            id: field.name + ',' + field.related_entity_name + '::' + child.name,
                            text: child.label,

                            value: child.name,
                            type: child.type,
                            label: child.label
                        };
                    })
                };
            });

            return fields;
        }
    });
});
