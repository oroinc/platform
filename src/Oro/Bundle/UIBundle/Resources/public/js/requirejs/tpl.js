/**
 * Allow to subscribe to application mediator events through requireJS
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
