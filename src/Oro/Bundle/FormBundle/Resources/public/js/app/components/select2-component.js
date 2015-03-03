/*jslint nomen: true*/
/*global define*/
define(['underscore', 'oroui/js/app/components/base/component', 'oroui/js/tools', 'jquery.select2'
    ], function (_, BaseComponent, tools) {
    'use strict';

    /**
     * Creates a view passed through 'view' option and binds it with _sourceElement
     */
    var ViewComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = options;
            this.prepareOptions();
            options._sourceElement.select2(this.select2Options);
        },
        prepareOptions: function () {
            this.select2Options = {};
        }
    });

    return ViewComponent;
});
