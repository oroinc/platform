/**
 * Allow to create underscore template functions through requireJS plugin
 *
 * Usage:
 * ```
 * define(function (require) {
 *     'use strict';
 *      var ConcreteView,
 *          BaseView = require('oroui/js/app/views/base/view');
 *
 *     ConcreteView = BaseView.extend({
 *         template: require('tpl!path/to/template.html'),
 *     });
 *     return ConcreteView;
 * });
 * ```
 */
define(['underscore'], function (_) {
    return {
        load: function (name, parentRequire, onLoad) {
            parentRequire(['text!' + name], function (text) {
                onLoad(_.template(text));
            });
        }
    };
});
