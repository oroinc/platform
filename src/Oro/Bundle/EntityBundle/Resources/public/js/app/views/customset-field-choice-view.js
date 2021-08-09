define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Select2View = require('oroform/js/app/views/select2-view');

    const CustomsetFieldChoiceView = Select2View.extend({
        defaultOptions: {
            select2: {
                dropdownAutoWidth: true,
                allowClear: false
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function CustomsetFieldChoiceView(options) {
            CustomsetFieldChoiceView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.defaultOptions, options);
            this.select2Config = this._prepareSelect2Options(options);
            CustomsetFieldChoiceView.__super__.initialize.call(this, options);
        },

        onChange: function(e) {
            const selectedItem = e.added || this.getData();
            this.trigger('change', selectedItem);
            CustomsetFieldChoiceView.__super__.onChange.call(this, e);
        },

        _prepareSelect2Options: function(options) {
            const select2Opts = _.clone(options.select2);

            if (select2Opts.formatSelectionTemplate) {
                const template = _.template(select2Opts.formatSelectionTemplate);
                select2Opts.formatSelection = this.formatSelection.bind(this, template);
            }

            return select2Opts;
        },

        formatSelection: function(template, item) {
            return _.isEmpty(item) ? '' : template(item);
        }
    });

    return CustomsetFieldChoiceView;
});
