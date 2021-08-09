define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const EmailTranslationView = BaseView.extend({
        events: {
            'shown.bs.tab [data-role="change-localization"]': 'onChangeLocalizationTab'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailTranslationView(options) {
            EmailTranslationView.__super__.constructor.call(this, options);
        },

        onChangeLocalizationTab: function(e) {
            const $target = $(e.target || window.event.target);
            const dataRelated = $target.attr('data-related');
            $($target.closest('form').find(':input.active-localization')).each(function(key, el) {
                $(el).val(dataRelated);
            });
        }
    });

    return EmailTranslationView;
});
