define(function(require) {
    'use strict';

    var EmailNotificationFormView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationFormView = BaseView.extend({
        selectors: null,

        defaults: {
            selectors: {
                form: 'form:first',
                listenChangeElements: []
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailNotificationFormView(options) {
            _.extend(this, this.defaults, _.pick(options, 'selectors'));

            EmailNotificationFormView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function() {
            EmailNotificationFormView.__super__.delegateEvents.call(this);

            _.forEach(this.selectors.listenChangeElements, function(selector) {
                this.delegate('change', selector, this.onChange.bind(this));
            }, this);
        },

        onChange: function() {
            mediator.execute('showLoading');

            var $form = this.$(this.selectors.form);
            var data = $form.serializeArray();

            data.push({name: 'reloadWithoutSaving', value: true});

            mediator.execute('submitPage', {
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: $.param(data)
            });
        }
    });

    return EmailNotificationFormView;
});
