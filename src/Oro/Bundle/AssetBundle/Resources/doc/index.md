## Table of Contents

- [Architecture](#architecture)
- [Usage](#usage)
  - [Build assets](#build-assets)
  - [Load SCSS or CSS files from the bundle](#load-scss-or-css-files-from-the-bundle)
    - [Location of `assets.yml` file](#location-of-assetsyml-file)
    - [Default entry points and output file names](#default-entry-points-and-output-file-names)
- [Commands](#commands)
  - [`oro:assets:build` command](#oroassetsbuild-command)
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

Next, run the [`oro:assets:build`](#commands) command to build assets with the webpack. During the first run it installs npm dependencies required for the build.

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
        <td><code>css/oro.css</code></td>
    </tr>
    <tr>
        <th>Store Front</th>
        <td><code>styles</code></td>
        <td><code>css/layout/[THEME_NAME]/styles.css</code></td>
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

#### `--watch|-w`

Turn on watch mode. This means that after the initial build,
webpack continues to watch the changes in any of the resolved files.

#### `--npm-install|-i`

Reinstall npm dependencies to `vendor/oro/platform/build` folder, to be used by webpack. Required when `node_modules` folder is absent or corrupted.

# Troubleshooting

## Error: Node Sass does not yet support your current environment
After the update of NodeJs you might experience an error because node modules were built on the old NodeJs version that is not compatible with the new one.

To fix the error, remove the existing node modules and re-build the assets:
```bash
rm -rf vendor/oro/platform/build/node_modules
php bin/console oro:assets:install
```

##  JS engine not found
Appears when configuration in cache is broken.

To fix the error, remove an application cache and warm it up:
```bash
rm -rf var/cache/*
php bin/console cache:warmup
```
