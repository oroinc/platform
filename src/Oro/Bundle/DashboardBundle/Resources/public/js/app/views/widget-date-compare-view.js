define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const WidgetDateCompareView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat(['useDateSelector', 'datepickerSelector']),

        datepickerSelector: '.datepicker-input',

        events: {
            'change [data-role="updateDatapicker"]': 'onChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function WidgetDateCompareView(options) {
            WidgetDateCompareView.__super__.constructor.call(this, options);
        },

        onChange: function(e) {
            const state = e.currentTarget.checked ? 'enable' : 'disable';
            this.$(this.datepickerSelector).datepicker(state);
        },

        render: function() {
            const $compareToDate = this.$(this.useDateSelector);

            if ($compareToDate.prop('checked') === false) {
                this.$(this.datepickerSelector).datepicker('disable');
            }

            return WidgetDateCompareView.__super__.render.call(this);
        }
    });

    return WidgetDateCompareView;
});

