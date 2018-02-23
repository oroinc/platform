define(function(require) {
    'use strict';

    var CustomsetFieldChoiceView;
    var $ = require('jquery');
    var _ = require('underscore');
    var Select2View = require('oroform/js/app/views/select2-view');

    CustomsetFieldChoiceView = Select2View.extend({
        defaultOptions: {
            select2: {
                dropdownAutoWidth: true,
                allowClear: false
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function CustomsetFieldChoiceView() {
            CustomsetFieldChoiceView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.defaultOptions, options);
            this.select2Config = this._prepareSelect2Options(options);
            CustomsetFieldChoiceView.__super__.initialize.call(this, options);
        },

        onChange: function(e) {
            var selectedItem = e.added || this.getData();
            this.trigger('change', selectedItem);
            CustomsetFieldChoiceView.__super__.onChange.call(this, e);
        },

        _prepareSelect2Options: function(options) {
            var select2Opts = _.clone(options.select2);

            if (select2Opts.formatSelectionTemplate) {
                var template = _.template(select2Opts.formatSelectionTemplate);
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
