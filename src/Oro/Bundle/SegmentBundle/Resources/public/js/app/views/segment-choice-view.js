define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const Select2View = require('oroform/js/app/views/select2-view');

    const SegmentChoiceView = Select2View.extend({
        defaultOptions: {
            entity: void 0,
            currentSegment: void 0,
            select2: {
                allowClear: false
            }
        },

        events: {
            change: 'onChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function SegmentChoiceView(options) {
            SegmentChoiceView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.defaultOptions, options);
            _.extend(this, _.pick(options, _.without(_.keys(this.defaultOptions), 'select2')));
            this.select2Config = this._processSelect2Options(options);
            SegmentChoiceView.__super__.initialize.call(this, options);
        },

        _processSelect2Options: function(options) {
            const opts = _.clone(options.select2) || {};

            if (!opts.formatSelectionTemplate && opts.formatSelectionTemplateSelector) {
                opts.formatSelectionTemplate = $(opts.formatSelectionTemplateSelector).text();
            }

            if (opts.formatSelectionTemplate) {
                const template = _.template(opts.formatSelectionTemplate);
                opts.formatSelection = function(item) {
                    return item && item.id ? template(item) : '';
                };
            }

            if (opts.ajax) {
                const currentSegment = this.currentSegment;
                opts.ajax = _.extend({}, opts.ajax, {
                    url: routing.generate(opts.ajax.url, {entityName: this.entity.replace(/\\/g, '_')}),
                    data: function(term, page) {
                        return {
                            page: page,
                            pageLimit: opts.pageLimit,
                            term: term,
                            currentSegment: currentSegment
                        };
                    },
                    results: function(data) {
                        return data;
                    }
                });
            }

            opts.initSelection = function(element, callback) {
                const data = element.data('data');
                if (!_.isEmpty(data)) {
                    callback(data);
                }
            };

            return opts;
        },

        onChange: function(e) {
            const selectedItem = e.added || this.getData();
            this.trigger('change', selectedItem);
        }
    });

    return SegmentChoiceView;
});
