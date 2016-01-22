define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    var FilterTemplate;

    FilterTemplate = {
        /**
         * Template for filter criteria
         *
         * @property
         */
        template: '',

        /**
         * Template selector for filter criteria
         * (should be defined for descendant filter)
         *
         * @property
         */
        templateSelector: '',

        /**
         * Filter decoration theme, empty string means default theme.
         * Gets appended to base templateSelector property
         *
         * @property
         */
        templateTheme: '',

        /**
         * Defines which template to use
         *
         * @private
         */
        _defineTemplate: function() {
            this.template = this._getTemplate(this.templateSelector);
        },

        _getTemplate: function(selector) {
            var theme = this.templateTheme;
            var src = theme && $(selector + '-' + theme).text() || $(selector).text();

            return _.template(src);
        }
    };

    return FilterTemplate;
});
