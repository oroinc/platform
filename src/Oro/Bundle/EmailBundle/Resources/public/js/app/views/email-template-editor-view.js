define(function(require) {
    'use strict';

    var EmailTemplateEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailTemplateEditorView = BaseView.extend({
        options: {
            typeSwitcher: 'input[name*="type"]', //type (Html or Plain) switcher selector
            hasWysiwyg: false, //is wysiwyg editor enabled in System->Configuration
            isWysiwygEnabled: false, //true if 'type' is set to 'Html'
            emailVariableView: {} // link to app/views/email-variable-view
        },
        listen: {
            'email-variable-view:click-variable mediator': '_onVariableClick'
        },
        events: {
            'change input[name*=type]': '_onTypeChange'
        },

        initialize: function(options) {
            EmailTemplateEditorView.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);

            this.render();
        },

        render: function() {
            this.initLayout().then(_.bind(this.afterLayoutInit, this));
        },

        afterLayoutInit: function() {
            this.options.hasWysiwyg = Boolean(this.$('textarea[name*="content"]:first').data('wysiwygEnabled'));
            if (this.options.hasWysiwyg) {
                this.options.isWysiwygEnabled = this.$(this.options.typeSwitcher).filter(':checked').val() === 'html';
                this.options.emailVariableView = this.pageComponent('email-template-variables');

                this._onEditorBlur();

                if (this.options.isWysiwygEnabled === false) {
                    this._switchWysiwygEditor(false);
                }
            }
        },

        _onVariableClick: function(field, value) {
            var fieldId = field.data('id');
            if (this.options.isWysiwygEnabled && !_.isUndefined(fieldId)) {
                this.forEachComponent(function(component) {
                    if (!_.isUndefined(component.view) &&
                        !_.isUndefined(component.view.tinymceConnected) &&
                        component.view.tinymceConnected === true &&
                        component.view.el.id === fieldId
                    ) {
                        component.view.tinymceInstance.execCommand('mceInsertContent', false, value);
                        component.view.tinymceInstance.execCommand('mceFocus', false, value);
                    }
                });
            }
        },

        _onEditorBlur: function() {
            if (this.options.hasWysiwyg && this.options.isWysiwygEnabled) {
                this.forEachComponent(function(component) {
                    if (!_.isUndefined(component.view) &&
                        !_.isUndefined(component.view.tinymceConnected) &&
                        !_.isNull(this.options.emailVariableView) &&
                        component.view.tinymceConnected === true
                    ) {
                        $(component.view.tinymceInstance.getBody()).on(
                            'blur',
                            _.bind(
                                function(e) {
                                    this.options.emailVariableView.view._updateElementsMetaData(e);
                                },
                                this
                            )
                        );
                    }
                });
            }
        },

        _onTypeChange: function(e) {
            if (this.options.hasWysiwyg) {
                var target = $(e.target);
                if (!target.is(':checked')) {
                    return;
                }

                if (target.val() === 'txt') {
                    this._switchWysiwygEditor(false);
                }
                if (target.val() === 'html') {
                    this._switchWysiwygEditor(true);
                    this._onEditorBlur();
                }
            }
        },

        _switchWysiwygEditor: function(enabled) {
            this.options.isWysiwygEnabled = enabled;
            this.forEachComponent(function(component) {
                if (!_.isUndefined(component.view) && !_.isUndefined(component.view.tinymceConnected)) {
                    component.view.setEnabled(enabled);
                }
            });

        }
    });

    return EmailTemplateEditorView;
});
