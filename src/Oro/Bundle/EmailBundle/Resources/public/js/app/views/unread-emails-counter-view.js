define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const UnreadEmailsStateHolder = require('oroemail/js/app/unread-emails-state-holder');

    const UnreadEmailsCounterView = BaseView.extend({
        listen: {
            'change:count model': 'render'
        },

        template: _.template('<%=(count < 100 ? count : "99+") %>'),

        /**
         * @inheritdoc
         */
        constructor: function UnreadEmailsCounterView(options) {
            UnreadEmailsCounterView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.model = UnreadEmailsStateHolder.getModel();
            this.model.set('count', options.count);
            UnreadEmailsCounterView.__super__.initialize.call(this, options);
        },

        render: function() {
            UnreadEmailsCounterView.__super__.render.call(this);
            this.$el.toggle(Number(this.model.get('count')) > 0);
            return this;
        }
    });

    return UnreadEmailsCounterView;
});

