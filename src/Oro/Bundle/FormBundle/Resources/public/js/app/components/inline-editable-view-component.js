define(function(require) {
    'use strict';

    var InlineEditableViewComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var InlineEditorWrapperView = require('../views/inline-editable-wrapper-view');
    var frontendTypeMap = require('../../tools/frontend-type-map');
    var overlayTool = require('oroui/js/tools/overlay');

    InlineEditableViewComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            // frontend type mapped to viewer/editor/reader
            var classes = frontendTypeMap[options.frontend_type];
            var dataReader = new classes.reader();
            this.wrapper = new InlineEditorWrapperView({
                el: options._sourceElement,
                autoRender: true
            });
            this.view = new classes.viewer(_.extend({
                el: this.wrapper.getContainer(),
                autoRender: true
            }, dataReader.read(options.value)));
            this.wrapper.on('start-editing', function() {

            });
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
            // this.resizeToCell(viewInstance);
            this.overlay = overlayTool.createOverlay(viewInstance.$el, overlayOptions);
            return viewInstance;
        }
    });

    return InlineEditableViewComponent;
});
