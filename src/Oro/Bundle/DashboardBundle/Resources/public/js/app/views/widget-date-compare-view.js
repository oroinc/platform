define(function(require) {
    'use strict';

    var WidgetDateCompareView;
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetDateCompareView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat(['useDateSelector', 'datepickerSelector']),

        datepickerSelector: '.datepicker-input',

        events: {
            'change [data-role="updateDatapicker"]': 'onChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetDateCompareView() {
            WidgetDateCompareView.__super__.constructor.apply(this, arguments);
        },

        onChange: function(e) {
            var state = e.currentTarget.checked ? 'enable' : 'disable';
            this.$(this.datepickerSelector).datepicker(state);
        },

        render: function() {
            var $compareToDate = this.$(this.useDateSelector);

            if ($compareToDate.prop('checked') === false) {
                this.$(this.datepickerSelector).datepicker('disable');
            }

            return WidgetDateCompareView.__super__.render.apply(this, arguments);
        }
    });

    return WidgetDateCompareView;
});

