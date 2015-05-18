define(function (require) {
    var JsplubmBaseView = require('./jsplumb-base'),
        _ = require('underscore'),
        JsplumbAreaView = require('./jsplumb-area'),
        JsplumbBoxView;

    JsplumbBoxView = JsplubmBaseView.extend({
        areaView: null,

        className: 'jsplumb-box',

        template: require('tpl!oroworkflow/templates/source.html'),

        initialize: function (options) {
            if (!(options.areaView instanceof JsplumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            JsplumbBoxView.__super__.initialize.apply(this, arguments);

            // append $el to the area view
            this.areaView.$el.append(this.$el);
        },

        render: function () {
            var instance = this.areaView.jsPlumbInstance;

            this.cleanup();
            this.ensureId();
            JsplumbBoxView.__super__.render.apply(this, arguments);

            if (this.model.get('position')) {
                this.$el.css({
                    top: this.model.get('position')[1],
                    left: this.model.get('position')[0]
                });
            }

            instance.batch(_.bind(function () {
                // add element as source to jsplumb
                if (this.model.get('draggable') !== false) {
                    instance.draggable(this.$el, {
                        stop: _.bind(function (e) {
                            // update model position when dragging stops
                            this.model.set({position: e.pos});
                        }, this)
                    });
                }
                instance.makeTarget(this.$el, {
                    dropOptions: { hoverClass: 'dragHover' },
                    anchor: 'Continuous',
                    allowLoopback: true
                });
                instance.makeSource(this.$el, {
                    filter: '.jsplumb-source',
                    anchor: 'Continuous',
                    connector: [ 'StateMachine', { curviness: 20 } ],
                    connectorStyle: {
                        strokeStyle: '#5c96bc',
                        lineWidth: 2,
                        outlineColor: 'transparent',
                        outlineWidth: 4
                    },
                    maxConnections: 50,
                    onMaxConnections: function (info, e) {
                        alert('Maximum connections (' + info.maxConnections + ') reached');
                    }
                });
            }, this));
        },

        cleanup: function () {
            var instance = this.areaView.jsPlumbInstance;
            instance.detach(this.$el);
        }
    });

    return JsplumbBoxView;
});
