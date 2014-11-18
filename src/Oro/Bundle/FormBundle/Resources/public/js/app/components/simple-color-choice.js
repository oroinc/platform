/*jslint nomen: true*/
/*global define*/
define(['underscore', 'oroui/js/app/components/base/component', 'jquery.simplecolorpicker'
    ], function (_, BaseComponent) {
    'use strict';

    var SimpleColorChoice = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this.$element = options._sourceElement;
            this.$element.simplecolorpicker(_.defaults(_.omit(options, ['_sourceElement']), {
                emptyColor: '#FFFFFF'
            }));
        },
        /**
         * @inheritDoc
         */
        dispose: function () {
            if (!this.disposed && this.$element) {
                this.$element.simplecolorpicker('destroy');
            }
            SimpleColorChoice.__super__.dispose.call(this);
        }
    });

    return SimpleColorChoice;
});
