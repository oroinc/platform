/*global define*/
define(function (require) {
    'use strict';

    var EmailTemplateEditorView,
        $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        BaseView = require('oroui/js/app/views/base/view');

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

        initialize: function (options) {
            EmailTemplateEditorView.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);

            this.render();
        },

        render: function () {
            this.initLayout().then(_.bind(this.afterLayoutInit, this));
        },

        afterLayoutInit: function () {
            this.options.hasWysiwyg = this.$('textarea[name*="content"]:first').data('wysiwygEnabled') == true;
            if (this.options.hasWysiwyg) {
                this.options.isWysiwygEnabled = this.$(this.options.typeSwitcher).filter(':checked').val() === 'html';
                this.options.emailVariableView = this.pageComponent('email-template-variables');

                this._onEditorBlur();

                if (this.options.isWysiwygEnabled == false) {
                    this._switchWysiwygEditor(false);
                }
            }
        },

        _onVariableClick: function (field, value) {
            var fieldId = field.data('id');
            if (this.options.isWysiwygEnabled && !_.isUndefined(fieldId)) {
                this.getComponentManager().forEachComponent(function (component) {
                    if (!_.isUndefined(component.view)
                        && !_.isUndefined(component.view.tinymceConnected)
                        && component.view.tinymceConnected === true
                        && component.view.el.id === fieldId
                    ) {
                        component.view.tinymceInstance.execCommand('mceInsertContent', false, value);
                    }
                }, this);
            }
        },

        _onEditorBlur: function () {
            if (this.options.hasWysiwyg && this.options.isWysiwygEnabled) {
                this.getComponentManager().forEachComponent(function (component) {
                    if (!_.isUndefined(component.view)
                        && !_.isUndefined(component.view.tinymceConnected)
                        && component.view.tinymceConnected === true
                    ) {
                        $(component.view.tinymceInstance.getBody()).on(
                            'blur',
                            _.bind(
                                function (e) {
                                    this.options.emailVariableView.view._updateElementsMetaData(e);
                                },
                                this
                            )
                        );
                    }
                }, this)
            }
        },

        _onTypeChange: function (e) {
            if (this.options.hasWysiwyg) {
                var type = $(e.target).val();
                if (type === 'txt') {
                    this._switchWysiwygEditor(false);
                }
                if (type === 'html') {
                    this._switchWysiwygEditor(true);
                    this._onEditorBlur();
                }
            }
        },

        _switchWysiwygEditor: function (enabled) {
            this.options.isWysiwygEnabled = enabled;
            this.getComponentManager().forEachComponent(function (component) {
                if (!_.isUndefined(component.view) && !_.isUndefined(component.view.tinymceConnected)) {
                    component.view.setEnabled(enabled);
                }
            }, this);

        }
    });

    return EmailTemplateEditorView;
});
