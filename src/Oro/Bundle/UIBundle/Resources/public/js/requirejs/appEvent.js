/**
 *
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
