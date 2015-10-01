define(function(require) {
    'use strict';

    var SelectEditorView;
    var TextEditorView = require('./text-editor-view');
    var $ = require('jquery');
    var _ = require('underscore');
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
            this.$('input[name=value]').select2(this.getSelect2Options());
            // select2 stops propagation of keydown event if key === ENTER
            // need to restore this functionality
            var events = {};
            events['keydown' + this.eventNamespace()] = _.bind(this.onInternalEnterKeydown, this);
            this.$('.select2-focusser').on(events);
            events = {};
            events['keydown' + this.eventNamespace()] = _.bind(this.onInternalTabKeydown, this);
            this.$('.select2-focusser').on(events);
        },

        getSelect2Options: function() {
            var options = [{
                id: '',
                text: ' '/* NOTE: this symbol is not space, but &nbsp; Please be patient */
            }];
            options.push.apply(options, this.availableChoices);
            return {
                placeholder: this.placeholder || ' ',
                allowClear: true,
                selectOnBlur: false,
                openOnEnter: false,
                data: {results: options}
            };
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.select2-focusser').off(this.eventNamespace());
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
