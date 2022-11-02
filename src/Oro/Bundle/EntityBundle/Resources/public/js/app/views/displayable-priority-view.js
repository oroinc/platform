define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const DisplayablePriorityView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat(['prioritySelector']),

        events: {
            'change [data-field="is_displayable"]': 'onChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function DisplayablePriorityView(options) {
            DisplayablePriorityView.__super__.constructor.call(this, options);
        },

        render: function() {
            this._setPriorityDisabledStateByElement($('select[data-field="is_displayable"]'));

            return DisplayablePriorityView.__super__.render.call(this);
        },

        onChange: function(e) {
            e.preventDefault();

            this._setPriorityDisabledStateByElement($(e.currentTarget));
        },

        _setPriorityDisabledStateByElement: function($el) {
            $(this.prioritySelector).prop('disabled', parseInt($el.val()) === 0);
        }
    });

    return DisplayablePriorityView;
});
