define(function(require) {
    'use strict';

    var EmailTranslationView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailTranslationView = BaseView.extend({
        events: {
            'shown.bs.tab [data-role="change-localization"]': 'onChangeLocalizationTab'
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailTranslationView() {
            EmailTranslationView.__super__.constructor.apply(this, arguments);
        },

        onChangeLocalizationTab: function(e) {
            var $target = $(e.target || window.event.target);
            var dataRelated = $target.attr('data-related');
            $($target.closest('form').find(':input.active-localization')).each(function(key, el) {
                $(el).val(dataRelated);
            });
        }
    });

    return EmailTranslationView;
});
