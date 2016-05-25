define(['jquery', 'underscore', 'routing', 'jquery-ui', 'jquery.select2'
    ], function($, _, routing) {
    'use strict';

    $.widget('orosegment.segmentChoice', {
        options: {
            entity: null,
            data: {},
            segmentData: {},
            select2: {
                collapsibleResults: true,
                dropdownAutoWidth: true
            },
            exclude: [],
            fieldsLoaderSelector: ''
        },

        _create: function() {
            this._on({
                change: function(e) {
                    if (e.added) {
                        this.element.trigger('changed', e.added.id);
                    }
                }
            });

            this._bindFieldsLoader();
        },

        _init: function() {
            this._processSelect2Options();
            this._updateData(this.options.entity, this.options.segmentData);
        },

        _setOption: function(key, value) {
            if ($.isPlainObject(value)) {
                $.extend(this.options[key], value);
            } else {
                this._super(key, value);
            }
            return this;
        },

        _getCreateOptions: function() {
            return $.extend(true, {}, this.options);
        },

        _processSelect2Options: function() {
            var template;
            var options = this.options.select2;

            if (options.formatSelectionTemplate) {
                template = _.template(options.formatSelectionTemplate);
                options.formatSelection = function(item) {
                    return item && item.id ? template(item) : '';
                };
            }

            var url = this.options.select2.ajax && routing.generate(
                this.options.select2.ajax.url,
                {
                    entityName: this.options.entity.replace(/\\/g, '_')
                }
            );

            this.options.select2.ajax = _.extend(
                {},
                this.options.select2.ajax,
                {
                    url: url,
                    data: _.bind(function(term, page) {
                        return {
                            page: page,
                            pageLimit: this.options.pageLimit,
                            term: term,
                            currentSegment: this.options.currentSegment
                        };
                    }, this),
                    results: function(data) {
                        return data;
                    }
                }
            );

            this.options.select2.initSelection = function(element, callback) {
                var data = element.data('data');
                if (!$.isEmptyObject(data)) {
                    callback(data);
                }
            };
        },

        _bindFieldsLoader: function() {
            var $fieldsLoader;
            if (!this.options.fieldsLoaderSelector) {
                return;
            }
            $fieldsLoader = $(this.options.fieldsLoaderSelector);

            this._updateData($fieldsLoader.val(), this.options.segmentData);
        },

        _updateData: function(entity, segmentData) {
            this.options.entity = entity;
            this.element
                .data('entity', entity)
                .data('data', segmentData);
            this.element.select2(this.options.select2);
        },

        setValue: function(value) {
            this.element.inputWidget('val', value, true);
        },

        setSelectedData: function(data) {
            this.element.inputWidget('valData', data);
        }
    });

    return $;
});
