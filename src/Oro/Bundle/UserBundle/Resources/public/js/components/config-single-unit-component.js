define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ConfigHideFieldsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                row_container: '.control-group',
                dependend_checkbox: '[data-depends-on-field="single_unit_mode"]'
            }
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $dependedEl: null,

        /**
         * @inheritdoc
         */
        constructor: function ConfigHideFieldsComponent(options) {
            ConfigHideFieldsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;
            this.$el.inputWidget('create');

            this.$form = this.$el.closest('form');
            const id = this.$el.data('dependency-id');

            this.$dependedEl = this.$form.find('[data-depends-on-field="' + id + '"]');

            this.updateDependentFields();
            this.$el.on('change.' + this.cid, this.updateDependentFields.bind(this));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('.' + this.cid);

            ConfigHideFieldsComponent.__super__.dispose.call(this);
        },

        updateDependentFields: function() {
            const isChecked = this.$el.prop('checked');
            if (isChecked) {
                this.$dependedEl.closest(this.options.selectors.row_container).show();
                this.$dependedEl.closest(this.options.selectors.row_container).closest('fieldset').show();
            } else {
                this.$dependedEl.closest(this.options.selectors.row_container).hide();
                this.$dependedEl.closest(this.options.selectors.row_container).closest('fieldset').hide();
            }
        }
    });

    return ConfigHideFieldsComponent;
});
