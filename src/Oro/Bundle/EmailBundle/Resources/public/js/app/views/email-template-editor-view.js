define(function(require) {
    'use strict';

    var EmailTemplateEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var DialogWidget = require('oro/dialog-widget');

    EmailTemplateEditorView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function EmailTemplateEditorView() {
            EmailTemplateEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
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

        _onPreview: function(event) {
            event.preventDefault();
            var $currentView = this.$el;

            var iframeId = 'preview-frame';
            var iframe = $('<iframe />', {
                name: iframeId,
                id: iframeId,
                frameborder: 0,
                marginwidth: 20,
                marginheight: 20,
                allowfullscreen: true
            });

            var formAction = $currentView.attr('action');

            $currentView.one('submit', function(e) {
                if (!e.result) {
                    return;
                }
                var confirmModal = new DialogWidget({
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
                        var tinymceInstance = component.view.tinymceInstance;
                        $(tinymceInstance.getBody()).on(
                            'blur',
                            _.bind(
                                function(e) {
                                    $(tinymceInstance.targetElm).trigger(e);
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
                }
            }
        },

        _switchWysiwygEditor: function(enabled) {
            this.options.isWysiwygEnabled = enabled;
            this.forEachComponent(function(component) {
                var view = component.view;

                if (!_.isUndefined(view) && !_.isUndefined(view.tinymceConnected)) {
                    view.setEnabled(enabled);
                    this.listenToOnce(view, 'TinyMCE:initialized', this._onEditorBlur.bind(this));
                }
            });
        }
    });

    return EmailTemplateEditorView;
});
