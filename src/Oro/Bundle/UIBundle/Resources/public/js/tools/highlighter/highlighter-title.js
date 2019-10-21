define(function(require, exports, module) {
    'use strict';

    var HighlighterTitle;
    var config = require('module-config').default(module.id);
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');

    var defaults = {
        prefix: '● '
    };

    HighlighterTitle = BaseClass.extend({
        /**
         * @inheritDoc
         */
        constructor: function HighlighterTitle(options) {
            HighlighterTitle.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var names = _.keys(defaults);
            _.extend(this, defaults, _.pick(config, names), _.pick(options, names));

            HighlighterTitle.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
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

    return HighlighterTitle;
});
