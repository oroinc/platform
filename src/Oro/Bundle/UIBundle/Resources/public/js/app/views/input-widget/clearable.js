define(function(require) {
    'use strict';

    var ClearableInputWidgetView;
    var $ = require('jquery');
    var template = require('tpl!oroui/templates/clearable.html');
    var AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');

    ClearableInputWidgetView = AbstractInputWidgetView.extend({
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
         * @inheritDoc
         */
        constructor: function ClearableInputWidgetView() {
            ClearableInputWidgetView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            var $container = $(this.template({placeholderIcon: this.$el.data('placeholder-icon')}));
            this.$input = this.$el;
            this.$el.after($container);
            $container.prepend(this.$input);

            this.setElement($container);

            return this;
        },

        /**
         * @inheritDoc
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
         * @inheritDoc
         */
        findContainer: function() {
            return this.$el;
        }
    });

    return ClearableInputWidgetView;
});
