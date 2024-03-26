define(function(require) {
    'use strict';

    const Backbone = require('backbone');

    function StubView(options) {
        const stub = Object.assign(Object.create(Backbone.Events), {
            $el: options.el,
            value: options.value,
            render() {
                this.$el.text(options.name);
                return this;
            },
            getValue() {
                return this.value;
            },
            dispose() {}
        });

        spyOn(stub, 'render', 'getValue').and.callThrough();

        return stub;
    }

    return StubView;
});
