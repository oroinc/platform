define(function(require) {
    'use strict';

    var SelectEditorView;
    var TextEditorView = require('./text-editor-view');
    var $ = require('jquery');
    require('jquery.select2');

    SelectEditorView = TextEditorView.extend({
        className: 'select-editor',

        initialize: function(options) {
            this.availableChoices = this.getAvailableOptions(options);
            SelectEditorView.__super__.initialize.apply(this, arguments);
        },

        getAvailableOptions: function(options) {
            var choices = options.column.get('metadata').choices;
            var result = [];
            for (var id in choices) {
                if (choices.hasOwnProperty(id)) {
                    result.push({
                        id: id,
                        text: choices[id]
                    });
                }
            }
            return result;
        },

        render: function() {
            SelectEditorView.__super__.render.call(this);
            var options = [{
                id: '',
                text: ' '/* NOTE: these symbol is not space, but &nbsp; Please be patient */
            }];
            options.push.apply(options, this.availableChoices);
            this.$('input[name=value]').select2({
                placeholder: this.placeholder || ' ',
                allowClear: true,
                data: {results: options}
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('input[name=value]').select2('destroy');
            // due to bug in select2
            $('body > .select2-drop-mask').remove();
            SelectEditorView.__super__.dispose.call(this);
        },

        focus: function() {
            this.$('input[name=value]').select2('open');
        },

        isChanged: function() {
            // current value is always string
            // btw model value could be an number
            return this.getValue() !== ('' + this.getModelValue());
        }
    });

    return SelectEditorView;
});
