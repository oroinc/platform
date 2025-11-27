import _ from 'underscore';
import BaseClass from 'oroui/js/base-class';
import moduleConfig from 'module-config';

const config = moduleConfig(module.id);
const defaults = {
    prefix: '● '
};

const HighlighterTitle = BaseClass.extend({
    /**
     * @inheritdoc
     */
    constructor: function HighlighterTitle(options) {
        HighlighterTitle.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        const names = _.keys(defaults);
        _.extend(this, defaults, _.pick(config, names), _.pick(options, names));

        HighlighterTitle.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.unhighlight();

        HighlighterTitle.__super__.dispose.call(this);
    },

    highlight: function() {
        if (document.title.substr(0, this.prefix.length) !== this.prefix) {
            document.title = this.prefix + document.title;
        }
    },

    unhighlight: function() {
        if (document.title.substr(0, this.prefix.length) === this.prefix) {
            document.title = document.title.substr(this.prefix.length);
        }
    }
});

export default HighlighterTitle;
