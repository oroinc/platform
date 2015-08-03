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
define({
    load: function(name, parentRequire, onLoad) {
        'use strict';

        parentRequire(['text!' + name, 'underscore'], function(text, _) {
            if (_) {
                onLoad(_.template(text));
            } else {
                onLoad();
            }
        });
    }
});
