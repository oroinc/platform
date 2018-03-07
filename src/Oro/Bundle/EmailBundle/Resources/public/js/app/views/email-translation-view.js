define(function(require) {
    'use strict';

    var EmailTranslationView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailTranslationView = BaseView.extend({
        events: {
            'show [data-role="change-language"]': 'onChangeLanguage'
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailTranslationView() {
            EmailTranslationView.__super__.constructor.apply(this, arguments);
        },

        onChangeLanguage: function(e) {
            var $target = $(e.target || window.event.target);
            var dataRelated = $target.attr('data-related');
            $($target.closest('form').find(':input.translation')).each(function(key, el) {
                $(el).val(dataRelated);
            });
        }
    });

    return EmailTranslationView;
});
