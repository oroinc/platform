define(function(require) {
    'use strict';

    var UnreadEmailsCounterView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var UnreadEmailsStateHolder = require('oroemail/js/app/unread-emails-state-holder');

    UnreadEmailsCounterView = BaseView.extend({
        listen: {
            'change:count model': 'render'
        },

        template: _.template('<%=(count < 100 ? count : "99+") %>'),

        /**
         * @inheritDoc
         */
        constructor: function UnreadEmailsCounterView() {
            UnreadEmailsCounterView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.model = UnreadEmailsStateHolder.getModel();
            this.model.set('count', options.count);
            UnreadEmailsCounterView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            UnreadEmailsCounterView.__super__.render.call(this);
            this.$el.toggle(Number(this.model.get('count')) > 0);
            return this;
        }
    });

    return UnreadEmailsCounterView;
});

