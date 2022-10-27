define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BaseView = require('oroui/js/app/views/base/view');
    const DialogWidget = require('oro/dialog-widget');

    const EmailTemplateEditorView = BaseView.extend({
        options: {
            typeSwitcher: 'input[name*="type"]', // type (Html or Plain) switcher selector
            hasWysiwyg: false, // is wysiwyg editor enabled in System->Configuration
            isWysiwygEnabled: false, // true if 'type' is set to 'Html'
            emailVariableView: {} // link to app/views/email-variable-view
        },

        listen: {
            'email-variable-view:click-variable mediator': '_onVariableClick'
        },

        events: {
            'change input[name*=type]': '_onTypeChange',
            'click .dialog-form-renderer': '_onPreview'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailTemplateEditorView(options) {
            EmailTemplateEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            EmailTemplateEditorView.__super__.initialize.call(this, options);

            this.options = _.defaults(options || {}, this.options);

            this.render();
        },

        render: function() {
            this.initLayout().then(this.afterLayoutInit.bind(this));
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

        _onPreview: function(event) {
            event.preventDefault();
            const $currentView = this.$el;

            const iframeId = 'preview-frame';
            const iframe = $('<iframe />', {
                name: iframeId,
                id: iframeId,
                frameborder: 0,
                marginwidth: 20,
                marginheight: 20,
                allowfullscreen: true
            });

            const formAction = $currentView.attr('action');

            $currentView.one('submit', function(e) {
                if (!e.result) {
                    return;
                }
                const confirmModal = new DialogWidget({
                    title: __('Preview'),
                    dialogOptions: {
                        modal: true,
                        resizable: true,
                        width: '85%',
                        height: '70%',
                        autoResize: true
                    }
                });
                confirmModal.render();
                confirmModal._onContentLoad('<div class="widget-content"></div>');
                confirmModal._showLoading();
                confirmModal.widget.addClass('dialog-single-iframe-container');
                confirmModal.$el.append(iframe);
                $currentView.attr('target', iframeId);
                $currentView.attr('action', $(event.currentTarget).attr('href'));

                iframe.one('load', function() {
                    confirmModal._hideLoading();
                    $currentView.removeAttr('target');
                    $currentView.attr('action', formAction);
                });

                // prevent navigation form processing
                e.stopImmediatePropagation();
            });

            $currentView.submit();
        },

        _onVariableClick: function(field, value) {
            if (this.options.isWysiwygEnabled) {
                this.forEachComponent(function(component) {
                    if (_.result(component.view, 'tinymceConnected') === true && component.view.$el.is(field)) {
                        component.view.tinymceInstance.execCommand('mceInsertContent', false, value);
                        component.view.tinymceInstance.execCommand('mceFocus', false, value);
                    }
                });
            }
        },

        _onEditorBlur: function() {
            if (this.options.hasWysiwyg && this.options.isWysiwygEnabled) {
                this.forEachComponent(function(component) {
                    if (_.result(component.view, 'tinymceConnected') === true &&
                        !_.isNull(this.options.emailVariableView)
                    ) {
                        const tinymceInstance = component.view.tinymceInstance;
                        if (!tinymceInstance) {
                            return;
                        }
                        $(tinymceInstance.getBody())
                            .off(`blur${component.view.eventNamespace()}`)
                            .on(`blur${component.view.eventNamespace()}`, e => {
                                $(tinymceInstance.targetElm).trigger(e);
                            });
                    }
                });
            }
        },

        _onTypeChange: function(e) {
            if (this.options.hasWysiwyg) {
                const target = $(e.target);
                if (!target.is(':checked')) {
                    return;
                }

                if (target.val() === 'txt') {
                    this._switchWysiwygEditor(false);
                }
                if (target.val() === 'html') {
                    this._switchWysiwygEditor(true);
                }
            }
        },

        _switchWysiwygEditor: function(enabled) {
            this.options.isWysiwygEnabled = enabled;
            this.forEachComponent(function(component) {
                const view = component.view;

                if (!_.isUndefined(view) && !_.isUndefined(view.tinymceConnected)) {
                    view.setIsHtml(enabled);
                    this.listenToOnce(view, 'TinyMCE:initialized', this._onEditorBlur.bind(this));
                }
            });
        }
    });

    return EmailTemplateEditorView;
});
