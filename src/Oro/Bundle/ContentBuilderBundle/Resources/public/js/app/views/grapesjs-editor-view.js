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

    /**
     * Create GrapesJS content builder
     * @type {*|void}
     */
    GrapesjsEditorView = BaseView.extend({
        /**
         * @inheritDoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'builderOptions', 'storageManager', 'builderPlugins', 'storagePrefix',
            'currentTheme', 'contextClass', 'canvasConfig'
        ]),

        /**
         * @inheritDoc
         */
        autoRender: true,

        /**
         * @property {GrapesJS.Instance}
         */
        builder: null,

        /**
         * Page context class
         * @property {String}
         */
        contextClass: 'body cms-page',

        /**
         * Main builder options
         * @property {Object}
         */
        builderOptions: {
            fromElement: true,

            /**
             * Color picker options
             * @property {Object}
             */
            colorPicker: {
                appendTo: 'body',
                showPalette: false
            }
        },

        /**
         * Storage prefix
         * @property {String}
         */
        storagePrefix: 'gjs-',

        /**
         * Storage options
         * @property {Object}
         */
        storageManager: {
            autosave: false,
            autoload: false
        },

        /**
         * Canvas options
         * @property {Object}
         */
        canvasConfig: {},

        /**
         * Style manager options
         * @property {Object}
         */
        styleManager: {
            clearProperties: 1
        },

        /**
         * Asset manager settings
         * @property {Object}
         */
        assetManagerConfig: {
            embedAsBase64: 1
        },

        /**
         * Themes list
         * @property {Array}
         */
        themes: [
            {
                name: 'blank',
                stylesheet: '/css/layout/blank/styles.css'
            },
            {
                name: 'default',
                stylesheet: '/css/layout/default/styles.css',
                active: true
            },
            {
                name: 'custom',
                stylesheet: '/css/layout/custom/styles.css'
            }
        ],
        /**
         * List of grapesjs plugins
         * @property {Object}
         */
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

        /**
         * @TODO Should refactored
         */
        getContainer: function() {
            var $editor = $('<div id="grapesjs" />');
            $editor.html(this.$el.val());
            this.$el.parent().append($editor);

            this.$el.hide();

            this.$container = $editor;

            return $editor.get(0);
        },

        /**
         * Initialize builder instance
         */
        initBuilder: function() {
            this.builder = GrapesJS.init(_.extend(
                {}
                , {
                    avoidInlineStyle: 1,
                    container: this.getContainer()
                }
                , this._prepareBuilderOptions()));

            mediator.trigger('grapesjs:created', this.builder);

            this.builderDelegateEvents();

            GrapesJSModules.call('components', {
                builder: this.builder
            });
        },

        /**
         * Add builder event listeners
         */
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

        /**
         * Remove builder event listeners
         */
        builderUndelegateEvents: function() {
            this.$el.closest('form').off(this.eventNamespace());
            this.builder.off();
        },

        /**
         * Get current theme
         * @returns {Object}
         */
        getCurrentTheme: function() {
            return _.find(this.themes, function(theme) {
                return theme.active;
            });
        },

        /**
         * Set active state for button
         * @param panel {String}
         * @param name {String}
         */
        setActiveButton: function(panel, name) {
            this.builder.Commands.run(name);
            var button = this.builder.Panels.getButton(panel, name);

            button.set('active', true);
        },

        /**
         * Add wrapper classes for iframe with content
         */
        _addClassForFrameWrapper: function() {
            $(this.builder.Canvas.getFrameEl().contentDocument).find('#wrapper').addClass(this.contextClass);
        },

        /**
         * Onload builder handler
         * @private
         */
        _onLoadBuilder: function() {
            GrapesJSModules.call('panel-manager', {
                builder: this.builder
            });

            this.setActiveButton('options', 'sw-visibility');
            this._addClassForFrameWrapper();

            mediator.trigger('grapesjs:loaded', this.builder);
        },

        /**
         * Update builder handler
         * @private
         */
        _onUpdatedBuilder: function() {
            mediator.trigger('grapesjs:updated', this.builder);
        },

        /**
         * Update components builder handler
         * @param state
         * @private
         */
        _onComponentUpdatedBuilder: function(state) {
            this._updateInitialField();
            mediator.trigger('grapesjs:components:updated', state);
        },

        /**
         * Update source textarea
         * @private
         */
        _updateInitialField: function() {
            var content = this.builder.getHtml();
            content += '<style>' + this.builder.getCss() + '</style>';
            this.$el.val(content).trigger('change');
        },

        /**
         * Collect and compare builder options
         * @returns {GrapesjsEditorView.builderOptions|{fromElement}}
         * @private
         */
        _prepareBuilderOptions: function() {
            _.extend(this.builderOptions
                , this._getPlugins()
                , this._getStorageManagerConfig()
                , this._getCanvasConfig()
                , this._getStyleManagerConfig()
                , this._getAssetConfig()
            );

            return this.builderOptions;
        },

        /**
         * Get extended Storage Manager config
         * @returns {{storageManager: (*|void)}}
         * @private
         */
        _getStorageManagerConfig: function() {
            return {
                storageManager: _.extend({}, this.storageManager, {
                    id: this.storagePrefix
                })
            };
        },

        /**
         * Get extended Style Manager config
         * @returns {{styleManager: *}}
         * @private
         */
        _getStyleManagerConfig: function() {
            return {
                styleManager: this.styleManager
            };
        },

        /**
         * Get extended Canvas config
         * @returns {{canvasCss: string, canvas: {styles: (*|string)[]}}}
         * @private
         */
        _getCanvasConfig: function() {
            var theme = this.getCurrentTheme();
            return _.extend({}, this.canvasConfig, {
                canvasCss: '.gjs-comp-selected { outline: 3px solid #0c809e !important; ' +
                    'outline-offset: 0 !important; }' +
                    '#wrapper { padding: 3px; }',
                canvas: {
                    styles: [theme.stylesheet]
                }
            });
        },

        /**
         * Get asset manager configuration
         * @returns {*|void}
         * @private
         */
        _getAssetConfig: function() {
            return {
                assetManager: this.assetManagerConfig
            };
        },

        /**
         * Get plugins list with options
         * @returns {{plugins: *, pluginsOpts: (GrapesjsEditorView.builderPlugins|{"gjs-preset-webpage"})}}
         * @private
         */
        _getPlugins: function() {
            return {
                plugins: _.keys(this.builderPlugins),
                pluginsOpts: this.builderPlugins
            };
        }
    });

    return GrapesjsEditorView;
});
