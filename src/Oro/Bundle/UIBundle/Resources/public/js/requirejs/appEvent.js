/**
 * Allow to subscribe to application mediator events through requireJS
 */
define({
    load: function (name, req, onLoad) {
        req(['oroui/js/mediator'], function (mediator) {
            mediator.once(name, function () {
                onLoad();
            });
        });
    }
});
