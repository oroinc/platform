/**
 * Allow to subscribe to application mediator events through requireJS
 */
define({
    load: function (name, req, onLoad) {
        req(['oroui/js/mediator', 'oroui/js/app/ready-state-tracker'], function (mediator, readyStateTracker) {
            readyStateTracker.whenReady(name, function () {
                onLoad();
            });
        });
    }
});
