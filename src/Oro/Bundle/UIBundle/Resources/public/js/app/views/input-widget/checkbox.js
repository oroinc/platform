define(function(require) {
    'use strict';

    var CheckboxInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var _ = require('underscore');
    var $ = require('jquery');
    var defaultTemplate = require('tpl!oroui/templates/checkbox/default-template.html');

    CheckboxInputWidget = AbstractInputWidget.extend({
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
        constructor: function CheckboxInputWidget() {
            return CheckboxInputWidget.__super__.constructor.apply(this, arguments);
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
         * Prepear custom checkbox markup
         * @private
         */
        _buildCustomCheckbox: function() {
            this.type = this.$el.attr('type');
            var label = this.$el.parent().find('> label');
            var labelText = label.html();
            var labelId = label.attr('for');
            label.remove();

            this.$checkboxContainer = $(this.template({
                label: labelText,
                labelId: labelId,
                type: this.type
            }));

            this.$checkboxContainer.toggleClass('empty-label', _.isEmpty(labelText));
            this.$el.after(this.$checkboxContainer);
            this.$el
                .addClass('checkbox-view__input')
                .prependTo(this.$checkboxContainer);
        },

        /**
         * @inheritDoc
         */
        disposeWidget: function() {
            this.$checkboxContainer.parent().append(this.$el);
            this.$checkboxContainer.remove();

            CheckboxInputWidget.__super__.disposeWidget.apply(this, arguments);
        }
    });

    return CheckboxInputWidget;
});
