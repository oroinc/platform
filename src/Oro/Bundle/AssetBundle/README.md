# OroAssetBundle

OroAssetBundle adds possibility to install project assets using webpack.

## `oro:assets:build` command
Run bin/webpack to build assets

### Usage

* `oro:assets:build [-w|--watch] [-i|--npm-install] [--] [<theme>]`

### Arguments

#### `theme`

Theme name to build. When not provided - all available themes will be built.

### Options

#### `--watch|-w`

Turn on watch mode. This means that after the initial build,
webpack will continue to watch for changes in any of the resolved files.

#### `--npm-install|-i`

Reinstall npm dependencies to vendor/oro/platform/build folder, to be used by webpack.Required when "node_modules" folder is corrupted.
