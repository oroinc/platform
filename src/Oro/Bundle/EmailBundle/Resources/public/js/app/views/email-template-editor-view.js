/*global define*/
define(function (require) {
    'use strict';

    var EmailVariableComponent1,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        BaseView = require('oroui/js/app/views/base/view');

    EmailVariableComponent1 = BaseView.extend({
        options: {
            typeSwitcher: 'input[name*="type"]', //selector of type switcher
            hasWysiwyg: false, //is wysiwyg editor enabled in System->Configuration
            isWysiwygEnabled: false, //true if 'type' is set to 'Html'
            editorComponents: [], //collection of editor(s) view components
            emailVariableview: {} // link to app/views/email-variable-view
        },

        initialize: function (options) {
            EmailVariableComponent1.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);

            this.render();
        },

        render: function () {
            mediator.execute('layout:init', this.$el, this).then(_.bind(this.afterLayoutInit, this));
        },

        afterLayoutInit: function () {
            this.options.hasWysiwyg = $(this.el).find('textarea[name*="content"]:first').data('wysiwygEnabled') == true;
            if (this.options.hasWysiwyg) {
                this.options.isWysiwygEnabled = $(this.options.typeSwitcher).filter(':checked').val() === 'html';
                this.options.emailVariableview = this.pageComponent('email-template-variables');

                this._collectEditors();
                this._initTypeSwitcher();
                this._listenToVariableClickHandler();
            }
        },

        _listenToVariableClickHandler: function () {
            mediator.on(
                'email-variable-view:click-variable',
                _.bind(function(field, value) {
                    var fieldId = field.data('id');
                    if (this.options.isWysiwygEnabled && !_.isUndefined(fieldId)) {
                        _.each(
                            this.options.editorComponents,
                            function (editor) {
                                if (editor.view.el.id === fieldId) {
                                    editor.view.tinymceInstance.execCommand('mceInsertContent', false, value);
                                    return;
                                }
                            }
                        )
                    }
                }, this)
            )
        },

        _collectEditors: function () {
            this.options.editorComponents = [];
            if (this.options.hasWysiwyg) {
                _.each(this.pageComponents, function (component) {
                    if (!_.isUndefined(component.view)
                        && !_.isUndefined(component.view.tinymceConnected)
                        && component.view.tinymceConnected === true
                    ) {
                        this.options.editorComponents.push(component);
                        $(component.view.tinymceInstance.getBody()).on(
                            'blur',
                            _.bind(
                                function (e) {
                                    this.options.emailVariableview.view._updateElementsMetaData(e);
                                },
                                this
                            )
                        );
                    }
                }, this)
            }
        },

        _initTypeSwitcher: function () {
            var switcher = $(this.el).find(this.options.typeSwitcher);
            switcher.on('change', _.bind(this._handleTypeChange, this));
            if (this.options.isWysiwygEnabled == false) {
                this._switchWysiwygEditor(false);
            }
        },

        _handleTypeChange: function (e) {
            var type = $(e.target).val();
            if (this.options.hasWysiwyg) {
                if (type === 'txt') {
                    this._switchWysiwygEditor(false);
                }
                if (type === 'html') {
                    this._switchWysiwygEditor(true);
                    this._collectEditors();
                }
            }
        },

        _switchWysiwygEditor: function (enable) {
            this.options.isWysiwygEnabled = enable;
            _.each(this.options.editorComponents, function (editor) {
                if (enable) {
                    editor.view.setEnabled(true);
                } else {
                    editor.view.setEnabled(false);
                }
            })
        }

    });

    return EmailVariableComponent1;
});
