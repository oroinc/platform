# OroAsseticBundle

OroAsseticBundle introduces configuration of CSS files groups via the assets.yml file, which can be configured in any active bundle. Such files are automatically merged and optimized for a web presentation.
 
## Overview
 
Example of an assets.yml file:

```yaml
assets:
    css:
        first_group:
            - 'First/Assets/Path/To/Css/first.css'
            - 'First/Assets/Path/To/Css/second.css'
            - 'First/Assets/Path/To/Css/third.css'
        second_group:
            - 'Second/Assets/Path/To/Css/first.css'
            - 'Second/Assets/Path/To/Css/second.css'
            - 'Second/Assets/Path/To/Css/third.css'
```

The CSS section contains groups of files. These groups can be excluded from optimization process to simplify debugging purposes.

The path to the file can be defined as bundles/bundle/path/to/file.ext. There is an `assets:install` command that should be
used for correct work.

To turn off compression of the css files in the `css_group` group, the following configuration should be added
to the config/config.yml (or config/config_{mode}.yml) file:

```yaml
oro_assetic:
    css_debug: [css_group]
```

To enable a debug mode for all CSS files, the following configuration should be used:

```yaml
oro_assetic:
    css_debug_all: true
```

After this configuration change, the cleanup and assets install is required:

```php
php bin/console cache:clear
php bin/console assets:install
```

To get the list of all available asset groups, the following command should be used:

```php
php bin/console oro:assetic:groups
```

The following code must be added to the main template:

```
    {% oro_css filter='array with filters' output='css/name_of_output_file.css' %}
        <link rel="stylesheet" media="all" href="{{ asset_url }}" />
    {% endoro_css %}
```
This tag is similar to the AsseticBundle `stylesheet` tag, but without the list of files.

## Excluding bundles from assetic

By default all bundles are included into assetic unless the whitelist is defined with the `assetic.bundles` configuration.
Oro Assetic bundle provides ability to exclude only specific bundles with `oro_assetic.excluded_bundles`

```yaml
oro_assetic:
    excluded_bundles:
        - DoctrineBundle
```

