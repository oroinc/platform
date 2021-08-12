define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const LocalizationsSelectView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function LocalizationsSelectView(options) {
            LocalizationsSelectView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            this.$select = this.$el.find(this.options.selectSelector);
            this.$select.on('change' + this.eventNamespace(), this.onSelectChange.bind(this));
            this.$useParent = this.$el.find(this.options.useParentSelector);

            mediator.on('default_localization:use_parent_scope', this.onDefaultLocalizationUseParentScope, this);
        },

        /**
         * Handles change event of the select field
         */
        onSelectChange: function() {
            const options = this.$select.find('option:selected');
            const selected = options.map(function(index, option) {
                const $option = $(option);

                return {
                    id: $option.val(),
                    label: $option.text()
                };
            });

            mediator.trigger('enabled_localizations:changed', selected);
        },

        /**
         * @param {Boolean} data
         */
        onDefaultLocalizationUseParentScope: function(data) {
            this.$useParent.prop('checked', data).change();
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$select.off('change' + this.eventNamespace());
            mediator.off(null, null, this);

            LocalizationsSelectView.__super__.dispose.call(this);
        }
    });

    return LocalizationsSelectView;
});
