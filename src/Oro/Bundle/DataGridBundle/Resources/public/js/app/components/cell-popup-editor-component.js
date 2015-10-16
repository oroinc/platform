define(function(require) {
    'use strict';

    var CellPopupEditorComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var overlayTool = require('oroui/js/tools/overlay');

    CellPopupEditorComponent = BaseComponent.extend({
        OVERLAY_TOOL_DEFAULTS: {
            position: {
                my: 'left top',
                at: 'left-1 top-4',
                collision: 'flipfit'
            }
        },

        initialize: function(options) {
            this.options = options || {};

            this.view = this.createView(options);

            // rethrow view events on component
            this.listenTo(this.view, 'all', function() {
                this.trigger.apply(this, arguments);
            }, this);

            CellPopupEditorComponent.__super__.initialize.apply(this, arguments);
        },

        createView: function(options) {
            var View = options.view;
            var viewInstance = new View(_.extend({}, options.viewOptions, {
                autoRender: true,
                model: options.cell.model,
                cell: options.cell,
                column: options.cell.column
            }));

            viewInstance.$el.addClass('inline-editor-wrapper');

            var overlayOptions = $.extend(true, {}, this.OVERLAY_TOOL_DEFAULTS, {
                position: {
                    of: options.cell.$el
                }
            });
            this.resizeToCell(viewInstance);
            this.overlay = overlayTool.createOverlay(viewInstance.$el, overlayOptions);
            return viewInstance;
        },

        /**
         * Resizes editor to cell width
         */
        resizeToCell: function(view) {
            view.$el.width(view.cell.$el.outerWidth() + this.getWidthIncrement());
        },

        /**
         * Returns cell editor width increment
         *
         * @returns {number}
         */
        getWidthIncrement: function() {
            return 64;
        },

        removeView: function() {
            this.view.dispose();
            this.overlay.remove();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.removeView();
            CellPopupEditorComponent.__super__.dispose.call(this);
        }
    });

    return CellPopupEditorComponent;
});
