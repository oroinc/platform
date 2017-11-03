define(function(require) {
    'use strict';

    var SegmentChoiceView;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui');
    require('jquery.select2');

    SegmentChoiceView = BaseView.extend({
        defaults: {
            entity: undefined,
            currentSegment: undefined
        },

        events: {
            change: 'onChange'
        },

        initialize: function(options) {
            options = _.defaults({}, options, this.defaults);
            _.extend(this, _.pick(options, _.keys(this.defaults)));
            this.select2Options = this._processSelect2Options(options);
            SegmentChoiceView.__super__.initialize.call(this, options);
        },

        _processSelect2Options: function(options) {
            var opts = _.clone(options.select2) || {};

            if (!opts.formatSelectionTemplate && opts.formatSelectionTemplateSelector) {
                opts.formatSelectionTemplate = $(opts.formatSelectionTemplateSelector).text();
            }

            if (opts.formatSelectionTemplate) {
                var template = _.template(opts.formatSelectionTemplate);
                opts.formatSelection = function(item) {
                    return item && item.id ? template(item) : '';
                };
            }

            if (opts.ajax) {
                var currentSegment = this.currentSegment;
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
                var data = element.data('data');
                if (!_.isEmpty(data)) {
                    callback(data);
                }
            }.bind(this);

            return opts;
        },

        onChange: function(e) {
            var selectedItem = e.added || this.$el.inputWidget('data');
            this.trigger('change', selectedItem);
        },

        render: function() {
            this.$el.inputWidget('create', 'select2', {initializeOptions: this.select2Options});
        },

        setValue: function(value) {
            this.$el.inputWidget('val', value, true);
        },

        setSelectedData: function(data) {
            this.$el.inputWidget('data', data);
        },

        getSelectedData: function() {
            return this.$el.inputWidget('data');
        }
    });

    return SegmentChoiceView;
});
