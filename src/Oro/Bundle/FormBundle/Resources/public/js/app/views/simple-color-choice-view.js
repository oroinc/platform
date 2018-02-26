define(['underscore', 'oroui/js/app/views/base/view', 'jquery.simplecolorpicker'
], function(_, BaseView) {
    'use strict';

    var SimpleColorChoiceView = BaseView.extend({
        events: {
            enable: 'enable',
            disable: 'disable'
        },

        /**
         * @inheritDoc
         */
        constructor: function SimpleColorChoiceView() {
            SimpleColorChoiceView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el.simplecolorpicker(_.defaults(_.omit(options, ['el']), {
                emptyColor: '#FFFFFF'
            }));
        },
        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.$el.data('simplecolorpicker')) {
                this.$el.simplecolorpicker('destroy');
            }
            SimpleColorChoiceView.__super__.dispose.call(this);
        },

        enable: function() {
            this.$el.simplecolorpicker('enable');
        },

        disable: function() {
            this.$el.simplecolorpicker('enable', false);
        }
    });

    return SimpleColorChoiceView;
});
