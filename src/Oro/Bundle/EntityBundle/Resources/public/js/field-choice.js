/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oro/entity-field-select-util', 'oro/entity-field-view',
    'jquery-ui', 'jquery.select2'
    ], function ($, _, EntityFieldUtil, EntityFieldView) {
    'use strict';

    $.widget('oroentity.fieldChoice', {
        options: {
            entity: null,
            fields: [],
            util: {},
            select2: {
                collapsibleResults: true,
                dropdownAutoWidth: true
            }
        },

        _create: function () {
            this.entityFieldUtil = new EntityFieldUtil(this.element);

            this._on({
                change: function (e) {
                    if (e.added) {
                        this.element.trigger('changed', e.added.id);
                    }
                }
            });
        },

        _init: function () {
            $.extend(this.entityFieldUtil, this.options.util);
            this.options.fields = this.entityFieldUtil._convertData(this.options.fields, this.options.entity, null);
            this.element
                .data('entity', this.options.entity)
                .data('data', this.options.fields);

            this._processSelect2Options();

            this.element.select2($.extend({
                data: this.options.fields
            }, this.options.select2));
        },

        _setOption: function (key, value) {
            if ($.isPlainObject(value)) {
                $.extend(this.options[key], value);
            } else {
                this._super(key, value);
            }
            return this;
        },

        _getCreateOptions: function () {
            return $.extend(true, {}, this.options);
        },

        _processSelect2Options: function () {
            var template, entityFieldUtil,
                options = this.options.select2;

            if (options.formatSelectionTemplate) {
                entityFieldUtil = this.entityFieldUtil;
                template = _.template(options.formatSelectionTemplate);
                options.formatSelection = function (item) {
                    return item.id ? template(entityFieldUtil.splitFieldId(item.id)) : '';
                };
            }
        },

        getApplicableConditions: function (fieldId) {
            return EntityFieldView.prototype.getFieldApplicableConditions.call(this.entityFieldUtil, fieldId);
        },

        setValue: function (value) {
            this.element.select2('val', value, true);
        }
    });

    return $;
});
