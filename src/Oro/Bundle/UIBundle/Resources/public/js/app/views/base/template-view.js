define(function(require) {
    'use strict';

    var TemplateView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    TemplateView = BaseView.extend({
        autoRender: true,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['data', 'template', 'events']));
            TemplateView.__super__.initialize.apply(this, arguments);
        },

        delegateEvents: function(events) {
            if (events) {
                return this._delegateEvents(events);
            }
            if (this.events) {
                this._delegateEvents(this.events);
                TemplateView.__super__.delegateEvents.call(this);
            } else {
                TemplateView.__super__.delegateEvents.call(this);
            }
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var data = this.getTemplateData();
            var template = this.getTemplateFunction();
            var html = template(data);
            this.$el.html(html);
        },

        /**
         * @inheritDoc
         * @returns {*}
         */
        getTemplateData: function() {
            return this.data;
        }
    });

    return TemplateView;
});
