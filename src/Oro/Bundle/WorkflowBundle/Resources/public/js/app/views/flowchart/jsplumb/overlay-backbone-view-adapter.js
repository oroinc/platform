define(function (require) {
    var JsplubmBackboneViewOverlayAdapter,
        Backbone = require('backbone'),
        jsPlumb = require('jsplumb');

    /*
     * Class: Overlays.Label
     */
    JsplubmBackboneViewOverlayAdapter = Backbone.Model.extend.call(jsPlumb.Overlays.Label, {
        constructor: function (view) {
            this.view = view;
            this.view.on('render', function () {
                this.clearCachedDimensions();
                this.update();
                this.component.repaint();
            }, this);
            jsPlumb.Overlays.Label.apply(this, _.toArray(arguments).slice(1));
        },
        update: function () {
            if (this.getElement() !== this.view.el) {
                this.view.setElement(this.getElement());
                this.view.render();
            }
        }
    });

    return JsplubmBackboneViewOverlayAdapter;
});
