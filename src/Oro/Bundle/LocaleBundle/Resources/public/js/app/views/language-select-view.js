define(function(require) {
    'use strict';

    var LanguageSelectView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    LanguageSelectView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectSelector: 'select'
        },

        /**
         * @property {jQuery.Element}
         */
        $select: null,

        /**
         * @inheritDoc
         */
        constructor: function LanguageSelectView() {
            LanguageSelectView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            this.$select = this.$el.find(this.options.selectSelector);

            mediator.on('supported_languages:changed', this.onSupportedLanguagesChanged, this);
        },

        /**
         * @inheritDoc
         */
        dispose: function(options) {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);
        },

        /**
         * @param {Object} data
         */
        onSupportedLanguagesChanged: function(data) {
            var select = this.$select;
            var selected = select.val();

            select.find('option[value!=""]').remove().val('').change();

            _.each(data, function(language) {
                select.append($('<option></option>').attr('value', language.id).text(language.label));
            });

            if (selected) {
                select.val(selected);

                if (selected !== select.val()) {
                    select.val('');
                }
            }

            select.change();
        }
    });

    return LanguageSelectView;
});
