define({
    load: function(name, req, onload, config) {
        req(config.appmodules || [], onload);
    }
});
