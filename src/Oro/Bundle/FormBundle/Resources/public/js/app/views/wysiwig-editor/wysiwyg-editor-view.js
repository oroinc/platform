define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const $ = require('jquery');
    const tools = require('oroui/js/tools');
    const __ = require('orotranslation/js/translator');
    const txtHtmlTransformer = require('./txt-html-transformer');
    const LoadingMask = require('oroui/js/app/views/loading-mask-view');
    const tinyMCE = require('tinymce/tinymce');

    const WysiwygEditorView = BaseView.extend({
        TINYMCE_UI_HEIGHT: 3,
        TEXTAREA_UI_HEIGHT: 22,

        autoRender: true,
        firstRender: true,
        firstQuoteLine: void 0,

        tinymceConnected: false,
        height: false,
        tinymceInstance: null,

        defaults: {
            enabled: true,
            isHtml: true,
            width: '100%',
            min_height: 250,
            plugins: ['code', 'lists', 'advlist', 'image'],
            pluginsMap: {},
            menubar: false,
            toolbar: ['undo redo | formatselect | bold italic underline | forecolor backcolor | bullist numlist' +
            '| alignleft aligncenter alignright alignjustify | image'],
            toolbar_mode: 'sliding',
            elementpath: false,
            branding: false,
            browser_spellcheck: true,
            file_picker_types: 'image',
            file_picker_callback: function(callback, value, meta) {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.onchange = () => {
                    const file = input.files[0];
                    const reader = new FileReader();
                    reader.onload = event => {
                        callback(event.target.result, {alt: file.name});
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            },
            paste_data_images: false,
            block_formats: [
                `${__('oro.form.tinymce.paragraph')}=p`,
                `${__('oro.form.tinymce.h1')}=h1`,
                `${__('oro.form.tinymce.h2')}=h2`,
                `${__('oro.form.tinymce.h3')}=h3`,
                `${__('oro.form.tinymce.h4')}=h4`,
                `${__('oro.form.tinymce.h5')}=h5`,
                `${__('oro.form.tinymce.h6')}=h6`
            ].join(';')
        },

        events: {
            'set-focus': 'setFocus',
            'wysiwyg:enable': 'enableEditor',
            'wysiwyg:disable': 'disableEditor'
        },

        /**
         * @inheritdoc
         */
        constructor: function WysiwygEditorView(options) {
            WysiwygEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.defaults, options);
            this.enabled = options.enabled;
            this.isHtml = options.isHtml;
            if (this.firstRender && !this.autoRender) {
                this.enabled = false;
            }
            this.options = _.omit(options, 'enabled', 'el');
            if (tools.isIOS()) {
                this.options.plugins = _.without(this.options.plugins, 'fullscreen');
                this.options.toolbar = this.options.toolbar.map(function(toolbar) {
                    return toolbar.replace(/\s*\|\s?fullscreen/, '');
                });
            }
            WysiwygEditorView.__super__.initialize.call(this, options);
        },

        render: function() {
            if (this.tinymceConnected) {
                if (!this.tinymceInstance) {
                    throw new Error('Cannot disable tinyMCE before its instance is created');
                }
                this.tinymceInstance.remove();
                this.tinymceInstance = null;

                // strip tags when disable HTML editing mode
                if (!this.isHtml) {
                    this.htmlValue = this.$el.val();
                    this.strippedValue = txtHtmlTransformer.html2text(this.htmlValue);
                    this.$el.val(this.strippedValue);
                }

                this.$el.show();
                this.tinymceConnected = false;
            }

            if (this.enabled) {
                this.connectTinyMCE();
                this.$el.attr('data-focusable', true);
                this.findFirstQuoteLine();
            } else {
                this.$el.removeAttr('data-focusable');
            }

            this.firstRender = false;
            this.$el.trigger('rendered', {tinymceConnected: this.tinymceConnected});
            this.trigger('resize');
        },

        connectTinyMCE: function() {
            const self = this;
            let loadingMaskContainer = this.$el.parents('.ui-dialog');
            if (!loadingMaskContainer.length) {
                loadingMaskContainer = this.$el.parent();
            }
            this.subview('loadingMask', new LoadingMask({
                container: loadingMaskContainer
            }));
            this.subview('loadingMask').show();
            if (!this.firstRender) {
                if (this.htmlValue && this.$el.val() === this.strippedValue) {
                    // if content is not modified, return html representation back
                    this.$el.val(this.htmlValue);
                } else {
                    this.$el.val(txtHtmlTransformer.text2html(this.$el.val()));
                }
            }
            this._deferredRender();
            const options = this.options;
            if ($(this.$el).prop('disabled') || $(this.$el).prop('readonly')) {
                options.readonly = true;
            }

            if (options.toolbar_mode && _.isArray(options.toolbar)) {
                // The toolbar modes are not available when using multiple toolbars or the toolbar(n) option.
                options.toolbar = options.toolbar.join(' | ');
            }

            _.each(this.options.pluginsMap, function(url, name) {
                url = location.origin + '/' + this.options.assets_base_url + url;
                if (this.options.plugins.indexOf(name) !== -1) {
                    tinyMCE.PluginManager.load(name, url);
                }
            }, this);

            tinyMCE.init(_.extend({
                target: this.el,
                setup: function(editor) {
                    editor.on('keydown', function(e) {
                        if (e.keyCode === 27) {
                            _.defer(function() {
                                // action is deferred to give time for tinymce to process the event by itself first
                                self.$el.trigger(e);
                            });
                        }
                    });
                    editor.on('change', function() {
                        editor.save({no_events: true});
                    });
                    editor.on('SetContent', function() {
                        editor.save({no_events: true});
                    });
                    editor.on('focusout', function() {
                        editor.save();
                    });
                },
                init_instance_callback: function(editor) {
                    self.removeSubview('loadingMask');
                    self.tinymceInstance = editor;
                    self.tinymceInstance.parser.addNodeFilter('#cdata', function(nodes) {
                        _.each(nodes, function(node) {
                            node.value = node.value.replace(/\[CDATA\[([^]*?)\]\]/g, '$1');
                        });
                    });

                    self.trigger('TinyMCE:initialized');
                    _.delay(function() {
                        /**
                         * fixes jumping dialog on refresh page
                         * (promise should be resolved in a separate process)
                         */
                        self._resolveDeferredRender();
                    }, 20);
                }
            }, options));

            this.tinymceConnected = true;
            this.deferredRender.fail(function() {
                self.removeSubview('loadingMask');
                self.tinymceInstance = null;
                self.tinymceConnected = false;
                self.$el.css('visibility', '');
            });
        },

        setEnabled: function(enabled) {
            if (this.enabled === enabled) {
                return;
            }
            this.enabled = enabled;
            this.render();
        },

        /**
         * @param {boolean} isHtml
         */
        setIsHtml: function(isHtml) {
            if (this.isHtml === isHtml) {
                return;
            }
            this.isHtml = isHtml;
            this.setEnabled(isHtml);
        },

        setFocus: function(e) {
            if (this.enabled) {
                this.tinymceInstance.focus();
            }
        },

        getHeight: function() {
            return this.$el.parent().innerHeight();
        },

        findFirstQuoteLine: function() {
            const quoteElement = $.parseHTML('<div>' + this.$el[0].value + '</div>');
            let quote = $(quoteElement).find('.quote').html();
            if (quote) {
                quote = txtHtmlTransformer.html2text(quote);
                this.firstQuoteLine = _.find(quote.split(/(\n\r?|\r\n?)/g), function(line) {
                    return line.trim().length > 0;
                });
                if (this.firstQuoteLine) {
                    this.firstQuoteLine = this.firstQuoteLine.trim();
                }
            } else {
                this.firstQuoteLine = void 0;
            }
        },

        getFirstQuoteLine: function() {
            return this.firstQuoteLine;
        },

        setHeight: function(newHeight) {
            if (this.tinymceConnected) {
                this.tinymceInstance.editorContainer.style.height = `${newHeight}px`;
            } else {
                this.$el.height(newHeight - this.TEXTAREA_UI_HEIGHT);
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.tinymceInstance) {
                this.tinymceInstance.remove();
                this.tinymceInstance = null;
            }
            WysiwygEditorView.__super__.dispose.call(this);
        },

        enableEditor: function() {
            this.setEnabled(true);
        },

        disableEditor: function() {
            this.setEnabled(false);
        }
    });

    return WysiwygEditorView;
});
