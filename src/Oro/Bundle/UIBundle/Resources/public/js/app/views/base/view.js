/*jslint nomen:true, eqeq:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'chaplin'
], function ($, _, Chaplin) {
    'use strict';

    var BaseView;

    /**
     * @export  oroui/js/app/views/base/view
     * @class   oroui.app.views.BaseView
     * @extends Chaplin.View
     */
    BaseView = Chaplin.View.extend({

        getTemplateFunction: function () {
            var template, templateFunc;
            template = this.template;
            templateFunc = null;

            if (typeof template === 'string') {
                templateFunc = _.template(template);
                // share a compiled template with all instances built with same constructor
                this.constructor.prototype.template = templateFunc;
            } else {
                templateFunc = template;
            }

            return templateFunc;
        }
    });

    return BaseView;
});
