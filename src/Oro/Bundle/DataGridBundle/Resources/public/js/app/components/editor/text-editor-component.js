define(function(require) {
    'use strict';

    var TextEditorComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var TextEditorView = require('../../views/editor/text-editor-view');

    TextEditorComponent = BaseComponent.extend({
        initialize: function(options) {
            this.options = options || {};

            this.view = new TextEditorView({
                autoRender: true,
                el: options._sourceElement,
                model: options.model
            });

            this.listenTo(this.view, 'saveAction', this.onSave);
            this.listenTo(this.view, 'cancelAction', this.onCancel);

            TextEditorComponent.__super__.initialize.apply(this, arguments);
        },

        onSave: function() {
            this.trigger('saveAction');
        },

        onCancel: function() {
            this.trigger('cancelAction');
        }
    });

    return TextEditorComponent;
});
