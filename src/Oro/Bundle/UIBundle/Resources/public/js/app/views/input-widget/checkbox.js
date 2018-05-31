define(function(require) {
    'use strict';

    var CheckboxInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var $ = require('jquery');
    var defaultTemplate = require('tpl!oroui/templates/checkbox/default-template.html');

    CheckboxInputWidget = AbstractInputWidget.extend({
        template: defaultTemplate,

        $checkboxContainer: null,

        type: null,
        /**
         * @inheritDoc
         * @returns {*}
         */
        constructor: function CheckboxInputWidget() {
            return CheckboxInputWidget.__super__.constructor.apply(this, arguments);
        },

        findContainer: function() {
            return this.$el;
        },

        initializeWidget: function() {
            this._buildCustomCheckbox();
        },

        _buildCustomCheckbox: function() {
            this.type = this.$el.attr('type');
            var label = this.$el.parent().find('> label').html();
            this.$el.parent().find('> label').remove();

            this.$checkboxContainer = $(this.template({
                label: label,
                type: this.type
            }));
            this.$checkboxContainer.toggleClass('empty-label', _.isEmpty(label));

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
