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
            selectSelector: 'select',
            useParentSelector: 'input[type="checkbox"]'
        },

        /**
         * @property {jQuery.Element}
         */
        $select: null,

        /**
         * @property {jQuery.Element}
         */
        $useParent: null,

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
            this.$useParent = this.$el.find(this.options.useParentSelector);
            this.$useParent.on('change' + this.eventNamespace(), _.bind(this.onUseParentChange, this));

            mediator.on('enabled_localizations:changed', this.onEnabledLocalizationsChanged, this);
        },

        /**
         * @inheritDoc
         */
        dispose: function(options) {
            if (this.disposed) {
                return;
            }

            this.$useParent.off('change' + this.eventNamespace());
            mediator.off(null, null, this);

            LocalizationSelectView.__super__.dispose.call(this);
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
        },

        onUseParentChange: function() {
            mediator.trigger('default_localization:use_parent_scope', this.$useParent.is(':checked'));
        }
    });

    return LocalizationSelectView;
});
