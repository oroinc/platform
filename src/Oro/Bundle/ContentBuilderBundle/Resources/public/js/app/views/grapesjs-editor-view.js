define(function(require) {
    'use strict';

    var GrapesjsEditorView;
    var BaseView = require('oroui/js/app/views/base/view');
    var GrapesJS = require('grapesjs');
    var $ = require('jquery');
    var _ = require('underscore');
    var GrapesJSModules = require('orocontentbuilder/js/app/views/grapesjs-modules/grapesjs-modules');
    var mediator = require('oroui/js/mediator');

    require('grapesjs-preset-webpage');

    GrapesjsEditorView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'builderOptions', 'storageManager', 'builderPlugins', 'storagePrefix',
            'currentTheme'
        ]),

        /**
         * @inheritDoc
         */
        autoRender: true,

        builder: null,

        builderOptions: {
            fromElement: true
        },

        storagePrefix: 'gjs-',

        storageManager: {
            autosave: false,
            autoload: false
        },

        canvasConfig: {},

        styleManager: {
            clearProperties: 1
        },

        colorPicker: {
            appendTo: '#grapesjs'
        },

        /**
         * @property {String}
         */
        currentTheme: 'default',

        builderPlugins: {
            'gjs-preset-webpage': {
                aviaryOpts: false,
                filestackOpts: null,
                blocksBasicOpts: {
                    flexGrid: 1
                },
                customStyleManager: GrapesJSModules.getModule('style-manager')
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function GrapesjsEditorView() {
            GrapesjsEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @param options
         */
        initialize: function(options) {
            GrapesjsEditorView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.initBuilder();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.builderUndelegateEvents();

            GrapesjsEditorView.__super__.dispose.call(this);
        },

        delegateEvents: function() {
            GrapesjsEditorView.__super__.delegateEvents.apply(this, arguments);
        },

        /**
         * @TODO Should refactored
         */
        getContainer: function() {
            var $editor = $('<div id="grapesjs" />');
            $editor.html(this.$el.val().replace('<font>', '<style>').replace('</font>', '</style>'));
            this.$el.parent().append($editor);

            this.$el.hide();

            this.$container = $editor;

            return $editor.get(0);
        },

        /**
         * Initialize builder instanse
         */
        initBuilder: function() {
            this.builder = GrapesJS.init(_.extend(
                {}
                , {
                    container: this.getContainer()
                }
                , this._prepareBuilderOptions()));

            mediator.trigger('grapesjs:created', this.builder);

            this.builderDelegateEvents();
        },

        builderDelegateEvents: function() {
            this.$el.closest('form').on(
                'keyup' + this.eventNamespace() + ' keypress' + this.eventNamespace()
                , _.bind(function(e) {
                    var keyCode = e.keyCode || e.which;
                    if (keyCode === 13 && this.$container.get(0).contains(e.target)) {
                        e.preventDefault();
                        return false;
                    }
                }, this));

            this.builder.on('load', _.bind(this._onLoadBuilder, this));
            this.builder.on('update', _.bind(this._onUpdatedBuilder, this));
            this.builder.on('component:update', _.bind(this._onComponentUpdatedBuilder, this));
        },

        builderUndelegateEvents: function() {
            this.$el.closest('form').off(this.eventNamespace());
            this.builder.off();
        },

        getCurrentThemeStylesheetURL: function() {
            return '/css/layout/' + this.currentTheme + '/styles.css';
        },

        _onLoadBuilder: function() {
            GrapesJSModules.call('panel-manager', {
                builder: this.builder
            });

            mediator.trigger('grapesjs:loaded', this.builder);
        },

        _onUpdatedBuilder: function() {
            mediator.trigger('grapesjs:updated', this.builder);
        },

        _onComponentUpdatedBuilder: function(state) {
            this._updateInitialField();
            mediator.trigger('grapesjs:components:updated', state);
        },

        _updateInitialField: function() {
            var content = this.builder.getHtml();
            content += '<font>' + this.builder.getCss() + '</font>';
            this.$el.val(content).trigger('change');
        },

        _prepareBuilderOptions: function() {
            _.extend(this.builderOptions
                , this._getPlugins()
                , this._getStorageManagerConfig()
                , this._getCanvasConfig()
                , this._getStyleManagerConfig()
            );

            return this.builderOptions;
        },

        _getStorageManagerConfig: function() {
            return {
                storageManager: _.extend({}, this.storageManager, {
                    id: this.storagePrefix
                })
            };
        },

        _getStyleManagerConfig: function() {
            return {
                styleManager: this.styleManager
            };
        },

        _getCanvasConfig: function() {
            var urlCSS = this.getCurrentThemeStylesheetURL();
            return {
                canvasCss: '.gjs-comp-selected { outline: 3px solid #0c809e !important; }',
                canvas: {
                    styles: [urlCSS]
                }
            };
        },

        _getPlugins: function() {
            return {
                plugins: _.keys(this.builderPlugins),
                pluginsOpts: this.builderPlugins
            };
        }
    });

    return GrapesjsEditorView;
});
