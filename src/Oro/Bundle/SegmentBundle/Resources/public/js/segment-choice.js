/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oroentity/js/entity-field-select-util', 'oroentity/js/entity-field-view',
    'jquery-ui', 'jquery.select2', 'routing'
], function ($, _, EntityFieldUtil, EntityFieldView, ui, select2, routing) {
    'use strict';

    function filterFields(fields, exclude) {
        fields = _.filter(fields, function (item) {
            var result;
            if (item.children) {
                item.children = filterFields(item.children, exclude);
                result = !_.isEmpty(item.children);
            } else {
                result = !_.some(exclude, function (rule) {
                    var cut = _.pick(item, _.keys(rule));
                    return _.isEqual(cut, rule);
                });
            }
            return result;
        });
        return fields;
    }

    $.widget('orosegment.segmentChoice', {
        options: {
            entity: null,
            fields: [],
            util: {},
            select2: {
                collapsibleResults: true,
                dropdownAutoWidth: true,
                minimumInputLength: 3
            },
            exclude: [],
            segmentsLoaderSelector: ''
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

            this._bindFieldsLoader();
        },

        _init: function () {
            $.extend(this.entityFieldUtil, this.options.util);
            this._processSelect2Options();
            this._updateData(this.options.entity, this.options.fields);
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

            this.options.select2.ajax = _.extend(
                {},
                this.options.select2.ajax,
                {
                    url: routing.generate(
                        this.options.select2.ajax.url,
                        {
                            entityName: this.options.entity.replace(/\\/g, '_'),
                            _format: 'json'
                        }
                    ),
                    data: function (term, page) {
                        return {
                            term: term
                        };
                    },
                    results: function (data, page) {
                        return {results: data};
                    }
                }
            );
        },

        _bindFieldsLoader: function () {
            var self = this, $fieldsLoader;
            if (!this.options.segmentsLoaderSelector) {
                return;
            }
            $fieldsLoader = $(this.options.segmentsLoaderSelector);
            $fieldsLoader.on('segmentsloaderupdate', function (e, fields) {
                self.setValue('');
                self._updateData($(e.target).val(), fields);
            });
            this._updateData($fieldsLoader.val(), $fieldsLoader.data('segments'));
        },

        _updateData: function (entity, fields) {
            this.options.entity = entity;
            this.options.fields = fields;
            fields = this.entityFieldUtil._convertData(fields, entity, null);
            if (!_.isEmpty(this.options.exclude)) {
                fields = filterFields(fields, this.options.exclude);
            }
            this.element
                .data('entity', entity)
                .data('data', fields);
            this.element.select2($.extend({data: fields}, this.options.select2));
        },

        setValue: function (value) {
            this.element.select2('val', value, true);
        }
    });

    return $;
});
