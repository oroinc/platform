## Table of Contents

- [Architecture](#architecture)
- [Usage](#usage)
  - [Build assets](#build-assets)
  - [Hot Module Replacement](#hot-module-replacement-hmr-or-hot-reload-for-scss) 
    - [Enable for CSS links](#enable-for-css-links)
    - [Usage](#usage-1)
    - [Enable HTTPS](#enable-https-for-hot-module-replacement)
    - [Usage in Production Environment](#usage-in-production-environment)  
  - [Load SCSS or CSS files from the bundle](#load-scss-or-css-files-from-the-bundle)
    - [Location of `assets.yml` file](#location-of-assetsyml-file)
    - [Default entry points and output file names](#default-entry-points-and-output-file-names)
- [Commands](#commands)
  - [`oro:assets:build` command](#oroassetsbuild-command)
- [Configuration Reference](#configuration-reference)
- [Troubleshooting](#troubleshooting)

# Architecture
Under the hood oro:assets:build command uses webpack to build assets.

The application contains `webpack.config.js` file that generates webpack configuration using `webpack-config-builder`.

Webpack __entry points__ with list of files are loaded from `assets.yml` files from all enabled Symfony bundles according to the bundles load priority.

To see the list of loaded bundles ordered by the priority, run: 
```bash
php bin/console debug:container --parameter=kernel.bundles --format=json
``` 
**Note:** 
_Entry point_ - is a group of assets that are loaded together, usually they are merged to a single file.

# Usage

## Build assets
First, run the `php bin/console assets:install --symlink` command  to symlink all assets' source files to `public/bundles/` directory. 

Next, run the [`php bin/console oro:assets:build`](#commands) command to build assets with the webpack. During the first run it installs npm dependencies required for the build.

## Hot Module Replacement (HMR or Hot Reload) For SCSS

Hot Module Replacement (HMR) exchanges, adds, or removes modules while an application is running, without a full reload. This can significantly speed up development.
For more details, see https://webpack.js.org/concepts/hot-module-replacement/.

### Enable for CSS links
To enable [HMR](#hot-module-replacement-hmr-or-hot-reload-for-scss) for CSS links in HTML we import CSS within Javascript. 
But for performance reasons, it is better to load plain CSS files at the production environment. 
To handle that automatically we render CSS with the following macro:
```twig
{% import '@OroAsset/Asset.html.twig' as Asset %}

{{ Asset.css('css/custom.css', 'media="all"')}}
```
That normally renders the link with rel stylesheet:
```html
<link rel="stylesheet" media="all" href="/css/custom.css"/>
```
But during development, when HMR is enabled and webpack-dev-server is listening at the background, this macro renders javascript tag that imports CSS dynamically and reloads it on changes, like:
```html
<script type="text/javascript" src="https://localhost:8081/css/custom.bundle.js"></script>
```

### Usage
To use HMR run the [`php bin/console oro:assets:build --hot`](#commands) command in the background, open the page you want to customize in a Web Browser and start editing SCSS files in an IDE. You will see the changes in a Browser instantly, without the need to reload the window. 

**Note:** 
To speed up the build operation provide the `theme` name as an argument:
```yaml
php bin/console oro:assets:build --hot -- default
```

### Enable HTTPS for Hot Module Replacement
In `config/config_dev.yml` file add the following lines:
```yaml
oro_asset:
    webpack_dev_server:
        https: true 
```
With the above setting, a self-signed certificate is used, but you can provide your own when running `oro:assets:build` command, for example:
```yaml
php bin/console oro:assets:build --hot --key=/path/to/server.key --cert=/path/to/server.crt --cacert=/path/to/ca.pem
# or
php bin/console oro:assets:build --hot --pfx=/path/to/file.pfx --pfx-passphrase=passphrase
```
### Usage in Production Environment
**Note:** 
Enablement of HMR for `prod` environment must not be committed to the git repository or published to the production web server for the performance reasons.

To enable HMR for `prod` environment add below lines to `config/config.yml`
```yaml
oro_asset:
    webpack_dev_server:
        enable_hmr: true 
```

## Load SCSS or CSS files from the bundle 
Create an `assets.yml` file that contains an entry point list with the files to load.
```yaml
css:                                                    # Entry point name. 
    inputs:                                             # List of files to load for `css` entry point
        - 'bundles/app/css/scss/main.scss'
    # You do not need to provide output for this entry point, it is already defined in 
    # vendor/oro/platform/src/Oro/Bundle/UIBundle/Resources/config/oro/assets.yml            
checkout:                                               # Another entry point name
    inputs:                                             # List of files to load for `checkout` entry point
        - 'bundles/app/scss/checkout_page_styles.scss'
    output: 'css/checkout-styles.css'                   # Output file path inside public/ directory for the `checkout` entry point
```

### Location of `assets.yml` file
<table>
    <tr>
        <th>Management Console</th>
        <td><code>[BUNDLE_NAME]/Resources/config/oro/assets.yml</code></td>
    </tr>
    <tr>
        <th>Store Front</th>
        <td><code>[BUNDLE_NAME]/Resources/views/layouts/[THEME_NAME]/config/assets.yml</code></td>
    </tr>        
</table>

### Default entry points and output file names
<table>
    <tr>
        <td></td>
        <td>entry point name</td>
        <td>output file</td>
    </tr>
    <tr>
        <th>Management Console</th>
        <td><code>css</code></td>
        <td><code>build/css/oro/oro.css</code></td>
    </tr>
    <tr>
        <th>Store Front</th>
        <td><code>styles</code></td>
        <td><code>layout/[THEME_NAME]/css/styles.css</code></td>
    </tr>        
</table>

**Note:** SCSS is the recommended format, CSS format is deprecated by `sass-loader` npm module.

# Commands
## `oro:assets:build` command
The command runs webpack to build assets.

In `dev` environment command builds assets without minification and with source maps.
In `prod` environment assets are minified and do not include source maps.
  
**Note:** When using the `watch` mode after changing the assets configuration at 
`assets.yml` files, it is required to restart the command, otherwise it will not detect the changes. 

### Usage

* `oro:assets:build [-w|--watch] [-i|--npm-install] [--] [<theme>]`
* `oro:assets:build admin.oro` to build assets only for default management-console theme, named `admin.oro`
* `oro:assets:build default --watch` to build assets only for `blank` theme with enabled `watch` mode

### Arguments

#### `theme`

Theme name to build. When not provided, all available themes are built.

### Options

#### `--hot`

Turn on hot module replacement. It allows all styles to be updated at runtime
without the need for a full refresh.

#### `--key`

SSL Certificate key PEM file path. Used only with hot module replacement.

#### `--cert`

SSL Certificate cert PEM file path. Used only with hot module replacement.

#### `--cacert`

SSL Certificate cacert PEM file path. Used only with hot module replacement.

#### `--pfx`

When used via the CLI, a path to an SSL .pfx file. If used in options, it should be the bytestream of the .pfx file.
Used only with hot module replacement.

#### `--pfxPassphrase`

The passphrase to a SSL PFX file. Used only with hot module replacement.

#### `--force-warmup|-f`

Warm up the asset-config.json cache.

#### `--watch|-w`

Turn on watch mode. This means that after the initial build,
webpack continues to watch the changes in any of the resolved files.

#### `--npm-install|-i`

Reinstall npm dependencies to `vendor/oro/platform/build` folder, to be used by webpack. Required when `node_modules` folder is corrupted.

# Configuration reference
AssetBundle defines configuration for NodeJs and NPM executable.

All these options are configured under the `oro_asset` key in your application configuration.

```yaml
# displays the default config values defined by Oro
 php bin/console config:dump-reference oro_asset
# displays the actual config values used by your application
 php bin/console debug:config oro_asset
```

## Configuration

### nodejs_path
**type: `string` required, default: found dynamically**

Path to NodeJs executable.

### npm_path
**type: `string` required, default: found dynamically**

Path to NPM executable.

### build_timeout
**type: `integer` required, default: `300`**

Assets build timeout in seconds, null to disable timeout.

### npm_install_timeout
**type: `integer` required, default: `900`**

Npm installation timeout in seconds, null to disable timeout.

### webpack_dev_server
Webpack Dev Server configuration

#### enable_hmr:
**type: `boolean` optional, default: `%kernel.debug%`**

Enable Webpack Hot Module Replacement. To activate HMR run `oro:assets:build --hot`

#### host
**type: `string` optional, default: `localhost`**

#### port
**type: `integer` optional, default: `8081`**

#### https
**type: `boolean` optional, default: `false`**

By default dev-server will be served over HTTP. It can optionally be served over HTTP/2 with HTTPS.

# Troubleshooting

## Error: Node Sass does not yet support your current environment
After the update of NodeJs you might experience an error because node modules were built on the old NodeJs version that is not compatible with the new one.

To fix the error, remove the existing node modules and re-build the assets:
```bash
rm -rf vendor/oro/platform/build/node_modules
php bin/console cache:clear
php bin/console oro:assets:build
```

##  JS engine not found
Appears when configuration in cache is broken.

To fix the error, remove an application cache and warm it up:
```bash
rm -rf var/cache/*
php bin/console cache:warmup
```
## Error: "output" for "assets" group in theme "oro" is not defined
Please follow [upgrade documentation](../../../../../../CHANGELOG.md#assetbundle-1) to update `assets.yml` files according to new requirements.

## Failed to load resource: net::ERR_CERT_AUTHORITY_INVALID
This happens because by default webpack-dev-server uses a self-signed SSL certificate. 

To fix an error we recommend to [provide your own 
SSL certificate](#enable-https-for-hot-module-replacement).

Alternatively, you can open stylesheet link in a new tab of a Browser, click "Show Advanced" and "Proceed to localhost (unsafe)". 
This loads the webpack-dev-server asset with a self-signed certificate.

## Error: listen EADDRINUSE: address already in use 127.0.0.1:8081
There are two cases when the error can appear
1. You exited the `oro:assets:build` command with <kbd>control</kbd> + <kbd>z</kbd> and `node` process hanged up. To fix, kill the `node` process manually.
2. The port is busy with some other process. To fix, change the [port configuration in config/config.yml](#port).
