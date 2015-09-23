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
                at: 'left-5 top-8',
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

            this.view.focus(!!options.fromPreviousCell);
        },

        createView: function(options) {
            var View = options.view;
            var viewInstance = new View(_.extend({}, options.viewOptions, {
                autoRender: true,
                el: $('<form class="inline-editor-wrapper"></form>'),
                model: options.cell.model,
                cell: options.cell,
                column: options.cell.column
            }));

            var overlayOptions = $.extend(true, {}, this.OVERLAY_TOOL_DEFAULTS, {
                position: {
                    of: options.cell.$el
                }
            });
            overlayTool.createOverlay(viewInstance.$el, overlayOptions);
            return viewInstance;
        },

        removeView: function() {
            overlayTool.removeOverlay(this.view.$el);
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
