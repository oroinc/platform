define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    const ThemeConfigurationDynamicRender = BaseView.extend({
        events: {
            'change [data-role="dynamic-render"]': 'onThemeChange'
        },

        selectors: null,

        defaults: {
            selectors: {
                form: 'form:first'
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function ThemeConfigurationDynamicRender(options) {
            _.extend(this, this.defaults, _.pick(options, 'selectors'));

            ThemeConfigurationDynamicRender.__super__.constructor.call(this, options);
        },

        onThemeChange: function() {
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

    return ThemeConfigurationDynamicRender;
});
