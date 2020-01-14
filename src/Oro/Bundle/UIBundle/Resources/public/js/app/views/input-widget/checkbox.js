define(function(require) {
    'use strict';

    const AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    const $ = require('jquery');
    const defaultTemplate = require('tpl-loader!oroui/templates/checkbox/default-template.html');

    const CheckboxInputWidget = AbstractInputWidget.extend({
        /**
         * @property {Template}
         */
        template: defaultTemplate,

        /**
         * @property {jQuery.Element}
         */
        $checkboxContainer: null,

        /**
         * @property {String}
         */
        type: null,

        /**
         * @inheritDoc
         * @returns {*}
         */
        constructor: function CheckboxInputWidget(options) {
            return CheckboxInputWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        findContainer: function() {
            return this.$el;
        },

        /**
         * @inheritDoc
         */
        initializeWidget: function() {
            this._buildCustomCheckbox();
        },

        /**
         * Prepare custom checkbox markup
         * @private
         */
        _buildCustomCheckbox: function() {
            this.type = this.$el.attr('type');

            const container = document.createElement('span');
            container.className = 'checkbox-view';
            container.innerHTML = this.template({
                type: this.type
            });

            this.$checkboxContainer = $(container);

            this.$el.after(this.$checkboxContainer);
            this.$el
                .addClass('checkbox-view__input')
                .prependTo(this.$checkboxContainer);
        },

        /**
         * @inheritDoc
         */
        disposeWidget: function() {
            this.$el.removeClass('checkbox-view__input');
            this.$checkboxContainer.before(this.$el);
            this.$checkboxContainer.remove();

            CheckboxInputWidget.__super__.disposeWidget.call(this);
        }
    });

    return CheckboxInputWidget;
});
