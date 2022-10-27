define(function(require) {
    'use strict';

    const $ = require('jquery');
    const template = require('tpl-loader!oroui/templates/clearable.html');
    const AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');

    const ClearableInputWidgetView = AbstractInputWidgetView.extend({
        refreshOnChange: true,

        widgetFunctionName: 'clearable',

        template: template,

        containerClass: 'clearable-input__container',

        $input: null,

        events: {
            'input input': 'refresh',
            'change input': 'refresh',
            'click .clearable-input__clear': 'onClear'
        },

        /**
         * @inheritdoc
         */
        constructor: function ClearableInputWidgetView(options) {
            ClearableInputWidgetView.__super__.constructor.call(this, options);
        },

        render: function() {
            const $container = $(this.template({placeholderIcon: this.$el.data('placeholder-icon')}));
            this.$input = this.$el;
            this.$el.after($container);
            $container.prepend(this.$input);

            this.setElement($container);

            return this;
        },

        /**
         * @inheritdoc
         */
        widgetFunction: function() {
            this.render();
            this.refresh();
        },

        refresh: function() {
            this.getContainer().toggleClass('clearable-input__container--clear', this.$input.val().length === 0);
        },

        onClear: function() {
            this.$input.val('').focus().trigger('input');
        },

        /**
         * @inheritdoc
         */
        findContainer: function() {
            return this.$el;
        }
    });

    return ClearableInputWidgetView;
});
