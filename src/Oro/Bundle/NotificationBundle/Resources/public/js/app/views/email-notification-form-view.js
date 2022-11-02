define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    const EmailNotificationFormView = BaseView.extend({
        selectors: null,

        defaults: {
            selectors: {
                form: 'form:first',
                listenChangeElements: []
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailNotificationFormView(options) {
            _.extend(this, this.defaults, _.pick(options, 'selectors'));

            EmailNotificationFormView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        delegateEvents: function() {
            EmailNotificationFormView.__super__.delegateEvents.call(this);

            _.forEach(this.selectors.listenChangeElements, function(selector) {
                this.delegate('change', selector, this.onChange.bind(this));
            }, this);
        },

        onChange: function() {
            mediator.execute('showLoading');

            const $form = this.$(this.selectors.form);
            const data = $form.serializeArray();

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
