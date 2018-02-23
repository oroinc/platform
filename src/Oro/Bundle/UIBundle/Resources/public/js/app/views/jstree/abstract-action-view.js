define(function(require) {
    'use strict';

    var AbstractActionView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    AbstractActionView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            $tree: '',
            action: '',
            template: require('tpl!oroui/templates/jstree-action.html'),
            icon: '',
            label: ''
        },

        /**
         * @inheritDoc
         */
        events: {
            'click [data-role="jstree-action"]': 'onClick'
        },

        /**
         * @inheritDoc
         */
        constructor: function AbstractActionView() {
            AbstractActionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            AbstractActionView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var $el = $(this.options.template(this.options));
            if (this.$el) {
                this.$el.replaceWith($el);
            }
            this.setElement($el);
            return this;
        },

        onClick: function() {},

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.options;
            AbstractActionView.__super__.dispose.apply(this, arguments);
        }
    });

    return AbstractActionView;
});
