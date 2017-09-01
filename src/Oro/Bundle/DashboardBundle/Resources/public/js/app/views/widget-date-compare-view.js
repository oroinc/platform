define(function(require) {
    'use strict';

    var WidgetDateCompareView;
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetDateCompareView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['useDateSelector', 'dateSelector']),

        events: {
            'change [data-role="updateDatapicker"]': 'onChange'
        },

        onChange: function(e) {
            var state = e.currentTarget.checked ? 'enable' : 'disable';
            this.$('.datepicker-input').datepicker(state);
        },

        render: function() {
            var $compareToDate = this.$(this.useDateSelector);

            if ($compareToDate.prop('checked') === false) {
                this.$(this.dateSelector).prop('readonly', true);
            }

            WidgetDateCompareView.__super__.render.apply(this, arguments);
        }
    });

    return WidgetDateCompareView;
});

