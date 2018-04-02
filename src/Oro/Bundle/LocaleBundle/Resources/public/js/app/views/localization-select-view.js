define(function(require) {
    'use strict';

    var LocalizationSelectView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    LocalizationSelectView = BaseView.extend({
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
        constructor: function LocalizationSelectView() {
            LocalizationSelectView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            this.$select = this.$el.find(this.options.selectSelector);

            mediator.on('enabled_localizations:changed', this.onEnabledLocalizationsChanged, this);
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
        onEnabledLocalizationsChanged: function(data) {
            var select = this.$select;
            var selected = select.val();

            select.find('option[value!=""]').remove().val('').change();

            _.each(data, function(localization) {
                select.append($('<option></option>').attr('value', localization.id).text(localization.label));
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

    return LocalizationSelectView;
});
