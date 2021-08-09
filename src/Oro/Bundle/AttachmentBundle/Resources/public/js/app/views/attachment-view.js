define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const AttachmentView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['inputSelector']),

        events: {
            'click [data-role="remove"]': 'onRemoveAttachment'
        },

        /**
         * @inheritdoc
         */
        constructor: function AttachmentView(options) {
            AttachmentView.__super__.constructor.call(this, options);
        },

        onRemoveAttachment: function(e) {
            e.preventDefault();
            this.$el.hide();
            this.$(this.inputSelector).val(true);
        }
    });

    return AttachmentView;
});
