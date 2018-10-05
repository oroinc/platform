define(function(require) {
    'use strict';

    var CheckboxInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
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
         * Prepare custom checkbox markup
         * @private
         */
        _buildCustomCheckbox: function() {
            this.type = this.$el.attr('type');

            var container = document.createElement('span');
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

            CheckboxInputWidget.__super__.disposeWidget.apply(this, arguments);
        }
    });

    return CheckboxInputWidget;
});
