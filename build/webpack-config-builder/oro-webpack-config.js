const webpack = require('webpack');
const AppConfigLoader = require('./app-config-loader');
const AppModulesFileWriter = require('./writer/app-modules-file-writer');
const CleanupStatsPlugin = require('./plugin/stats/cleanup-stats-plugin');
const ConfigsFileWriter = require('./writer/configs-file-writer');
const EntryPointFileWriter = require('./writer/scss-entry-point-file-writer');
const LayoutModulesConfigLoader = require('./modules-config/layout-modules-config-loader');
const LayoutStyleLoader = require('./style/layout-style-loader');
const MapModulesPlugin = require('./plugin/map/map-modules-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const HappyPack = require('happypack');
const ModulesConfigLoader = require('./modules-config/modules-config-loader');
const DynamicImportsFileWriter = require('./writer/dynamic-imports-file-writer');
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const prepareModulesShim = require('./prepare-modules-shim');
const StyleLoader = require('./style/style-loader');
const ThemeConfigFactory = require('./theme-config-factory');
const path = require('path');
const postcssConfig = path.join(__dirname, '../postcss.config.js');
const prepareModulesMap = require('./plugin/map/prepare-modules-map');
const resolve = require('enhanced-resolve');
const webpackMerge = require('webpack-merge');
const babelConfig = require('./../babel.config.js');

class ConfigBuilder {
    constructor() {
        this._publicPath = 'public/';
        this._adminTheme = 'admin.oro';
        this._enableLayoutThemes = false;
        this._defaultLayoutThemes = null;
    }

    /**
     * Set Symfony public directory path related to application root folder
     * @param {string} publicPath
     * @returns {ConfigBuilder}
     */
    setPublicPath(publicPath) {
        this._publicPath = publicPath;
        return this;
    }

    /**
     * Set Symfony cache directory path related to application root folder
     * @param {string} cachePath
     * @returns {ConfigBuilder}
     */
    setCachePath(cachePath) {
        this._cachePath = cachePath;
        return this;
    }

    /**
     * Set active admin (management console) theme. Out of the box there are 2 themes ("admin.oro" and "admin.demo")
     *
     * @param {string} adminTheme
     * @returns {ConfigBuilder}
     */
    setAdminTheme(adminTheme) {
        const themeNameParts = adminTheme.split(".");
        if (themeNameParts[0] !== 'admin' || themeNameParts.length !== 2) {
            throw new Error('Admin theme name should be in a format "admin.{themeName}", for example "admin.oro"');
        }

        this._adminTheme = adminTheme;
        return this;
    }

    /**
     * Enable build of Layout themes.
     */
    enableLayoutThemes(themes) {
        if (themes) {
            this._defaultLayoutThemes = themes;
        }
        this._enableLayoutThemes = true;
        return this;
    }

    /**
     * Returns callback that loads webpack configs based on environment and command arguments variables
     * @returns {Function}
     */
    getWebpackConfig() {
        return (env = {}, args = {}) => {
            this._initialize(args, env);

            let selectedTheme = env.theme;
            this._validateThemeName(selectedTheme);

            let themes = [];
            // Admin themes
            if (selectedTheme === undefined) {
                // themes.push(this._adminTheme.split(".")[1]);
                themes.push(this._adminTheme);
            } else if (this._adminThemes.indexOf(selectedTheme) !== -1) {
                // themes.push(selectedTheme.split(".")[1]);
                themes.push(selectedTheme);
            }

            // Layout Themes
            if (this._enableLayoutThemes) {
                if (selectedTheme === undefined) {
                    // build all layout themes
                    let layoutThemes = {};
                    if (this._defaultLayoutThemes) {
                        themes = [...themes, ...this._defaultLayoutThemes];
                    } else {
                        themes = [...themes, ...Object.keys(this._layoutModulesConfigLoader.themes)];
                    }
                } else if (this._layoutThemes.indexOf(selectedTheme) !== -1) {
                    // build single layout theme
                    themes.push(selectedTheme);
                }
            }

            const resolvedPublicPath = path.resolve(this._publicPath);

            const stats = env.stats || {
                hash: false,
                version: false,
                children: false,
                entrypoints: false,
                performance: this._isProduction,
                chunks: false,
                modules: false,
                source: false,
                publicPath: false,
                builtAt: false,
                warnings: false
            };
            let webpackConfig = {
                watchOptions: {
                    aggregateTimeout: 200,
                    ignored: [
                        /[\/\\]node_modules[\/\\].*\.js$/,
                        /[\/\\]bundles[\/\\](npmassets|components)[\/\\].*\.js$/
                    ]
                },
                stats: stats,
                output: {
                    filename: '[name].js',
                    // Because we use third party libraries 'chunkFilename' should include only [name]
                    chunkFilename: 'chunk/[name].js?version=[chunkhash:8]',
                },
                devtool: !env.skipSourcemap && 'inline-cheap-module-source-map',
                mode: 'development',
                optimization: {
                    namedModules: true,
                    splitChunks: {
                        cacheGroups: {
                            commons: {
                                name: 'commons',
                                minSize: 30,
                                minChunks: 2,
                                priority: 10,
                                reuseExistingChunk: true
                            },
                            vendors: {
                                test: /[\/\\]node_modules[\/\\]/,
                                name: 'vendors',
                                priority: -10
                            },
                            tinymce: {
                                test: /tinymce/,
                                name: 'tinymce.min',
                                minChunks: 1,
                            },
                            fusioncharts: {
                                test: /fusioncharts/,
                                name: 'fusioncharts',
                                minChunks: 1
                            }
                        }
                    }
                },
                resolveLoader: {
                    modules: [
                        resolvedPublicPath,
                        path.join(__dirname, './loader'),
                        resolvedPublicPath + '/bundles',
                        path.join(__dirname, '../node_modules'),
                    ]
                },
                module: {
                    noParse: [
                        /[\/\\]bundles[\/\\](npmassets|components)[\/\\](?!jquery|asap)[\/\\].*\.js$/,
                        /[\/\\]bundles[\/\\]\.*[\/\\]lib[\/\\](?!chaplin|bootstrap|jquery\.dialog).*\.js$/
                    ],
                    rules: [
                        {
                            test: /\.s?css$/,
                            use: [{
                                loader: args.hot ? 'style-loader' : MiniCssExtractPlugin.loader,
                            }, {
                                loader: 'css-loader',
                                options: {
                                    importLoaders: 1,
                                    sourceMap: true,
                                }
                            }, {
                                loader: 'postcss-loader',
                                options: {
                                    sourceMap: true,
                                    config: {
                                        path: postcssConfig,
                                    }
                                }
                            }, {
                                loader: 'resolve-url-loader',
                                options: {
                                    attempts: 0,
                                    sourceMap: true,
                                    keepQuery: true,
                                    root: '/',
                                    includeRoot: true
                                }
                            }, {
                                loader: 'sass-loader',
                                options: {
                                    includePaths: [
                                        resolvedPublicPath + '/bundles',
                                        path.resolve(__dirname, '../node_modules'),
                                    ],
                                    sourceMap: true
                                }
                            }]
                        },
                        {
                            test: /\.(eot|ttf|woff|woff2|cur|ico|svg|png|jpg|gif)$/,
                            loader: 'url-loader',
                            options: {
                                limit: 1,
                                emitFile: false,
                                publicPath: '../../../',
                                name: '[path][name].[ext]?[hash]'
                            }
                        }
                    ]
                },
                performance: {hints: false},
                plugins: [
                    new MiniCssExtractPlugin({
                        filename: '[name].css'
                    }),
                    new CleanupStatsPlugin(),
                    // Ignore all locale files of moment.js
                    new webpack.IgnorePlugin({
                        resourceRegExp: /^\.[\/\\]locale$/,
                        contextRegExp: /moment$/
                    }),
                    new webpack.optimize.MinChunkSizePlugin({
                        minChunkSize: 30000 // Minimum number of characters
                    })
                ]
            };

            if (!env.skipJS && !env.skipBabel) {
                let happyPackOptions = {
                    id: 'babel',
                    loaders: [
                        {
                            loader: 'babel-loader',
                            options: babelConfig
                        }
                    ]
                };

                webpackConfig.plugins.push(new HappyPack(happyPackOptions));

                webpackConfig.module.rules.push({
                    test: /\.js$/,
                    exclude: [
                        /[\/\\]platform[\/\\]build[\/\\]/,
                        /[\/\\]bundles[\/\\](?:npmassets|components)[\/\\]/,
                        /[\/\\]bundles[\/\\].+[\/\\]lib[\/\\]?/
                    ],
                    use: 'happypack/loader?id=babel'
                });
            }

            if (args.hot) {
                const https = this._appConfig.devServerOptions.https;
                const schema = https ? 'https' : 'http';
                const devServerHost = this._appConfig.devServerOptions.host;
                const devServerPort = this._appConfig.devServerOptions.port;
                webpackConfig.devServer = {
                    contentBase: resolvedPublicPath,
                    host: devServerHost,
                    port: devServerPort,
                    https: https,
                    compress: true,
                    stats: stats,
                    disableHostCheck: true,
                    clientLogLevel: 'error',
                    headers: {
                        'Access-Control-Allow-Origin': '*'
                    },
                };
                webpackConfig.output.publicPath = `${schema}://${devServerHost}:${devServerPort}/`;
            }

            //Additional setting for production mode
            if (this._isProduction) {
                webpackConfig = webpackMerge(webpackConfig, {
                    devtool: false,

                    plugins: [
                        new OptimizeCssAssetsPlugin({
                            cssProcessorOptions: {
                                discardComments: {
                                    removeAll: true
                                },
                                zindex: false
                            }
                        })
                    ]
                });
            }

            let webpackConfigs = [];

            themes.forEach((theme) => {
                let themeConfig,
                    buildPublicPath;
                if (this._isAdminTheme(theme)) {
                    buildPublicPath = '/build/';
                    themeConfig = this._themeConfigFactory.create(theme, buildPublicPath, '/Resources/config/jsmodules.yml');
                } else {
                    buildPublicPath = `/layout-build/${theme}/`;
                    themeConfig = this._layoutThemeConfigFactory.create(theme, buildPublicPath, '/config/jsmodules.yml');
                }
                let resolvedBuildPath = path.join(resolvedPublicPath, buildPublicPath);

                let resolverConfig = {
                    modules: [
                        resolvedBuildPath,
                        resolvedPublicPath,
                        resolvedPublicPath + '/bundles',
                        resolvedPublicPath + '/js',
                        path.join(__dirname, '../node_modules'),
                    ],
                    alias: {
                        ...themeConfig.aliases,
                        'node_modules/spectrum-colorpicker/spectrum$': 'npmassets/spectrum-colorpicker/spectrum.css',
                        'node_modules/font-awesome/scss/font-awesome$': 'npmassets/font-awesome/scss/font-awesome.scss',
                        'node_modules/codemirror/lib/codemirror$': 'npmassets/codemirror/lib/codemirror.css',
                        'node_modules/codemirror/theme/hopscotch$': 'npmassets/codemirror/theme/hopscotch.css',
                    },
                    symlinks: false
                };
                let resolver = (resolver => {
                    return moduleName => resolver({}, '', moduleName, {});
                })(resolve.create.sync({...resolverConfig}));

                let cssEntryPoints = !env.skipCSS ? this._getCssEntryPoints(theme, buildPublicPath) : {};
                let jsEntryPoints = !env.skipJS && Object.keys(themeConfig.aliases).length
                    ? this._getJsEntryPoints(theme) : {};

                let entryPoints = {...cssEntryPoints, ...jsEntryPoints};
                if (Object.keys(entryPoints).length === 0) {
                    return;
                }
                webpackConfigs.push(webpackMerge({
                    entry: entryPoints,
                    output: {
                        publicPath: buildPublicPath,
                        path: resolvedBuildPath,
                    },
                    context: resolvedPublicPath,
                    resolve: {
                        ...resolverConfig,
                        plugins: [
                            new MapModulesPlugin(prepareModulesMap(resolver, themeConfig.map))
                        ],
                    },
                    module: {
                        rules: [
                            {
                                test: /[\/\\]configs\.json$/,
                                loader: 'config-loader',
                                options: {
                                    resolver,
                                    relativeTo: resolvedPublicPath
                                }
                            },
                            ...prepareModulesShim(resolver, themeConfig.shim)
                        ]
                    }
                }, webpackConfig));
            });

            return webpackConfigs;
        };
    }

    _initialize(args, env) {
        this._isProduction = args.mode === 'production';
        this._symfonyEnv = env.symfony;
        this._appConfig = AppConfigLoader.getConfig(this._cachePath, this._symfonyEnv);

        this._modulesConfigLoader = new ModulesConfigLoader(
            this._appConfig.paths,
            '/Resources/public/themes/',
            'settings.yml'
        );
        this._adminThemes = this._modulesConfigLoader.themeNames.map(themeName => 'admin.' + themeName);
        this._styleLoader = new StyleLoader(this._modulesConfigLoader);
        this._themeConfigFactory = new ThemeConfigFactory(
            this._modulesConfigLoader,
            new DynamicImportsFileWriter(this._publicPath),
            new AppModulesFileWriter(this._publicPath),
            new ConfigsFileWriter(this._publicPath)
        );

        this._layoutModulesConfigLoader = new LayoutModulesConfigLoader(
            this._appConfig.paths,
            '/Resources/views/layouts/',
            'theme.yml'
        );
        this._layoutThemes = this._layoutModulesConfigLoader.themeNames;
        const entryPointFileWriter = new EntryPointFileWriter(this._publicPath);
        this._layoutStyleLoader = new LayoutStyleLoader(this._layoutModulesConfigLoader, entryPointFileWriter);
        this._layoutThemeConfigFactory = new ThemeConfigFactory(
            this._layoutModulesConfigLoader,
            new DynamicImportsFileWriter(this._publicPath),
            new AppModulesFileWriter(this._publicPath),
            new ConfigsFileWriter(this._publicPath)
        );

    }

    _getJsEntryPoints(theme) {
        return {
            'app': [
                'whatwg-fetch',
                'oroui/js/app',
                'oroui/js/app/services/app-ready-load-modules'
            ]
        };
    }

    _getCssEntryPoints(theme, buildPath) {
        if (this._isAdminTheme(theme)) {
            return this._styleLoader.getThemeEntryPoints(theme.split(".")[1]);
        }

        return this._layoutStyleLoader.getThemeEntryPoints(theme, buildPath);
    }

    _validateThemeName(theme) {
        let existingThemes = this._adminThemes;
        if (this._enableLayoutThemes) {
            existingThemes = existingThemes.concat(this._layoutThemes);
        }
        if (theme !== undefined && !existingThemes.includes(theme)) {
            throw new Error(
                'Theme "' + theme + '" doesn\'t exists. Existing themes:' + existingThemes.join(', ')
            );
        }
    }

    _isAdminTheme(theme) {
        return this._adminThemes.indexOf(theme) !== -1;
    }
}

module.exports = new ConfigBuilder;
