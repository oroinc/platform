/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oroentity/js/entity-field-select-util', 'oroentity/js/entity-field-view',
    'jquery-ui', 'jquery.select2', 'routing'
], function ($, _, EntityFieldUtil, EntityFieldView, ui, select2, routing) {
    'use strict';

    $.widget('orosegment.segmentChoice', {
        options: {
            entity: null,
            fields: [],
            segmentData: {},
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
            this._updateData(this.options.entity, this.options.segmentData);
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
            var template,
                options = this.options.select2;

            if (options.formatSelectionTemplate) {
                template = _.template(options.formatSelectionTemplate);
                options.formatSelection = function (item) {
                    return item && item.id ? template(item) : '';
                };
            }

            var url = this.options.select2.ajax && routing.generate(
                this.options.select2.ajax.url,
                {
                    entityName: this.options.entity.replace(/\\/g, '_'),
                    _format: 'json'
                }
            );

            this.options.select2.ajax = _.extend(
                {},
                this.options.select2.ajax,
                {
                    url: url,
                    data: function (term, page) {
                        return {
                            term: term
                        };
                    },
                    results: _.bind(function (data, page) {
                        var currentId = this.options.currentSegment;
                        var data = _.filter(data, function (item) {
                            if (item.id != 'segment_'+currentId) {
                                return true;
                            }
                        });
                        return {results: data};
                    }, this)
                }
            );

            this.options.select2.initSelection = function (element, callback) {
                var data = element.data('data');
                if (!$.isEmptyObject(data)) {
                    callback(data);
                }
            }
        },

        _bindFieldsLoader: function () {
            var $fieldsLoader;
            if (!this.options.segmentsLoaderSelector) {
                return;
            }
            $fieldsLoader = $(this.options.segmentsLoaderSelector);

            this._updateData($fieldsLoader.val(), this.options.segmentData);
        },

        _updateData: function (entity, segmentData) {
            this.options.entity = entity;
            this.element
                .data('entity', entity)
                .data('data', segmentData);
            this.element.select2(this.options.select2);
        },

        setValue: function (value) {
            this.element.select2('val', value, true);
        },

        setSelectedData: function (data) {
            this.element.select2('data', data);
        }
    });

    return $;
});
