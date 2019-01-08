const path = require('path');
const webpackMerge = require('webpack-merge');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const CleanupStatsPlugin = require('./cleanup-stats-plugin');

const BundlesLoader = require('./bundles-loader');
const ConfigLoader = require('./config-loader');
const StyleLoader = require('./style-loader');
const LayoutStyleLoader = require('./layout-style-loader');
const EntryPointFileWriter = require('./entry-point-file-writer');
const postcssConfig = path.join(__dirname, '../postcss.config.js');


class ConfigBuilder {
    constructor() {
        this._publicPath = 'public/';
        this._adminTheme = 'admin.oro';
        this._enableLayoutThemes = false;
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
     * Enable build of Layout themes. To learn more see
     * {@link https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/LayoutBundle/Resources/doc/theme_definition.md}
     */
    enableLayoutThemes() {
        this._enableLayoutThemes = true;
        return this;
    }

    /**
     * Returns callback that loads webpack configs based on environment and command arguments variables
     * @returns {Function}
     */
    getWebpackConfig() {
        return (env, args) => {
            this._isProduction = args.mode === 'production';
            let theme = env ? env.theme : undefined;
            this._symfonyEnv = env ? env.symfony : undefined;

            const resolvedPublicPath = path.resolve(this._publicPath);
            let webpackConfig = {
                stats: {
                    warnings: false
                },
                context: resolvedPublicPath,
                entry: this._getEntryPoints(theme),
                output: {
                    path: resolvedPublicPath,
                    filename: '[name].bundle.js',
                },
                devtool: 'inline-cheap-module-source-map',
                mode: 'none',
                resolve: {
                    modules: [
                        resolvedPublicPath,
                        resolvedPublicPath + '/bundles',
                        path.join(__dirname, '../node_modules'),
                    ],
                    symlinks: false
                },
                resolveLoader: {
                    modules: [
                        resolvedPublicPath,
                        resolvedPublicPath + '/bundles',
                        path.join(__dirname, '../node_modules'),
                    ]
                },

                module: {
                    rules: [
                        {
                            test: /\.s?css$/,
                            use: [{
                                loader: MiniCssExtractPlugin.loader,
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
                                publicPath: '/',
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
                    new CleanupStatsPlugin()
                ]
            };

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

            return webpackConfig;
        };
    }

    /**
     * @param {string|undefined} selectedTheme
     * @return {Object} List of entry points
     * @private
     */
    _getEntryPoints(selectedTheme) {
        const bundles = BundlesLoader.getBundles(this._cachePath, this._symfonyEnv);

        const configLoader = new ConfigLoader(bundles, '/Resources/public/themes/', 'settings.yml');
        const entryPointFileWriter = new EntryPointFileWriter(this._publicPath);
        const styleLoader = new StyleLoader(configLoader);
        const layoutConfigLoader = new ConfigLoader(bundles, '/Resources/views/layouts/', 'theme.yml');

        let entryPoints = {};
        const adminThemes = configLoader.themeNames.map(themeName => 'admin.' + themeName);
        const layoutThemes = layoutConfigLoader.themeNames;

        this._validateSelectedThemeName(adminThemes, layoutThemes, selectedTheme);

        // Build Admin themes
        if (selectedTheme === undefined) {
            entryPoints = styleLoader.getThemeEntryPoints(this._adminTheme.split(".")[1]);
        }
        else if (adminThemes.indexOf(selectedTheme) !== -1) {
            entryPoints = styleLoader.getThemeEntryPoints(selectedTheme.split(".")[1]);
        }

        // Build Layout Themes
        if (this._enableLayoutThemes) {
            const layoutStyleLoader = new LayoutStyleLoader(layoutConfigLoader, entryPointFileWriter);
            if (selectedTheme === undefined) {
                // build all layout themes
                for (let theme in layoutConfigLoader.themes) {
                    entryPoints = Object.assign({}, entryPoints, layoutStyleLoader.getThemeEntryPoints(theme));
                }
            } else if (layoutThemes.indexOf(selectedTheme) !== -1) {
                // build single layout theme
                entryPoints = Object.assign({}, entryPoints, layoutStyleLoader.getThemeEntryPoints(selectedTheme));
            }
        }

        return entryPoints;
    }

    _validateSelectedThemeName(adminThemes, layoutThemes, selectedTheme) {
        let existingThemes = adminThemes;
        if (this._enableLayoutThemes) {
            existingThemes = existingThemes.concat(layoutThemes);
        }
        if (selectedTheme !== undefined && !existingThemes.includes(selectedTheme)) {
            throw new Error(
                'Theme "' + selectedTheme + '" doesn\'t exists. Existing themes:' + existingThemes.join(', ')
            );
        }
    }
}

module.exports = new ConfigBuilder;
