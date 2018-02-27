define(function(require) {
    'use strict';

    var SidePanelView;
    var BaseView = require('./base/view');

    SidePanelView = BaseView.extend({
        autoRender: true,

        /**
         * @inheritDoc
         */
        constructor: function SidePanelView() {
            SidePanelView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            SidePanelView.__super__.render.call(this);
            this.initLayout();
            return this;
        }
    });

    return SidePanelView;
});
