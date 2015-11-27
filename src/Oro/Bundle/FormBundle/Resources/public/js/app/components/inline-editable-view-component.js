define(function(require) {
    'use strict';

    var InlineEditableViewComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var InlineEditorWrapperView = require('../views/inline-editable-wrapper-view');
    var frontendTypeMap = require('../../tools/frontend-type-map');
    var overlayTool = require('oroui/js/tools/overlay');

    InlineEditableViewComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.fieldName = options.fieldName || 'value';
            // frontend type mapped to viewer/editor/reader
            var classes = frontendTypeMap[options.frontend_type];
            this.classes = classes;
            this.viewer = options.viewer || {};
            this.editor = options.editor || {};
            this.model = new BaseModel();
            this.model.set(this.fieldName, options.value);
            this.wrapper = new InlineEditorWrapperView({
                el: options._sourceElement,
                autoRender: true
            });
            this.view = new classes.viewer(_.extend({
                el: this.wrapper.getContainer(),
                autoRender: true,
                model: this.model,
                fieldName: 'value'
            }));
            this.wrapper.on('start-editing', this.enterEditMode, this);
        },

        enterEditMode: function() {
            var View = this.classes.editor;
            var viewInstance = new View(_.extend({}, this.editor.viewOptions, {
                autoRender: true,
                model: this.model,
                fieldName: this.fieldName
            }));

            viewInstance.$el.addClass('inline-editor-wrapper');

            var overlayOptions = $.extend(true, {}, this.OVERLAY_TOOL_DEFAULTS, {
                position: {
                    of: this.wrapper.$el
                }
            });
            this.resizeTo(viewInstance, this.wrapper);
            this.overlay = overlayTool.createOverlay(viewInstance.$el, overlayOptions);
            return viewInstance;
        },

        /**
         * Resizes editor to cell width
         */
        resizeTo: function(view, cell) {
            view.$el.css({
                width: cell.$el.outerWidth()
            });
        }
    });

    return InlineEditableViewComponent;
});
