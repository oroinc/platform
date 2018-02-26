define(function(require) {
    'use strict';

    var DisplayablePriorityView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    DisplayablePriorityView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat(['prioritySelector']),

        events: {
            'change [data-field="is_displayable"]': 'onChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function DisplayablePriorityView() {
            DisplayablePriorityView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            this._setPriorityDisabledStateByElement($('input[data-field="is_displayable"]'));

            return DisplayablePriorityView.__super__.render.apply(this, arguments);
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
