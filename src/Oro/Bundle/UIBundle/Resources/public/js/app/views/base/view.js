/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin'
], function (_, Chaplin) {
    'use strict';

    var View = Chaplin.View.extend({

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

    return View;
});
