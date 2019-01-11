define(function(require) {
    'use strict';

    var LocalizationsSelectView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    LocalizationsSelectView = BaseView.extend({
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
        constructor: function LocalizationsSelectView() {
            LocalizationsSelectView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            this.$select = this.$el.find(this.options.selectSelector);
            this.$select.on('change' + this.eventNamespace(), _.bind(this.onSelectChange, this));
            this.$useParent = this.$el.find(this.options.useParentSelector);

            mediator.on('default_localization:use_parent_scope', this.onDefaultLocalizationUseParentScope, this);
        },

        /**
         * Handles change event of the select field
         */
        onSelectChange: function() {
            var options = this.$select.find('option:selected');
            var selected = options.map(function(index, option) {
                var $option = $(option);

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
         * {@inheritDoc}
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
