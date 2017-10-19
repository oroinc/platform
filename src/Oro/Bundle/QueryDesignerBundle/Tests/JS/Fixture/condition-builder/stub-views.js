define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');

    function StubView(options) {
        var stub = _.extend(Object.create(Backbone.Events), {
            $el: options.el,
            value: options.value,
            render: function() {
                this.$el.text(options.name);
                return this;
            },
            getValue: function() {
                return this.value;
            },
            dispose: function() {}
        });

        spyOn(stub, 'render', 'getValue').and.callThrough();

        return stub;
    }

    return StubView;
});
