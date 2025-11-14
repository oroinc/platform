import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import 'jquery.simplecolorpicker';

const SimpleColorChoiceView = BaseView.extend({
    events: {
        enable: 'enable',
        disable: 'disable'
    },

    /**
     * @inheritdoc
     */
    constructor: function SimpleColorChoiceView(options) {
        SimpleColorChoiceView.__super__.constructor.call(this, options);
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
     * @inheritdoc
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

export default SimpleColorChoiceView;
