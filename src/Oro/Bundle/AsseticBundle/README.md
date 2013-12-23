OroAsseticBundle
================

OroAsseticBundle is based on AsseticBundle and enables expandable and optimized way to manage CSS assets that are
distributed across many bundles.

With OroAsseticBundle developer can define CSS files groups in assets.yml configuration of the bundle. Defined files
will be automatically merged and optimized for web presentation. For development and debug purposes some files can
be excluded from optimization process.

Example of assets.yml file:
```yaml
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

CSS section contain groups of files. This groups can be excluded from optimization process debugging purposes.

The path to file can be defined as bundles/bundle/path/to/file.ext. There is a command "assets:install" that should be
used for correct work.

To turn off compression of css files in 'css_group' group the following configuration should be added
to app/config/config.yml (or app/config/config_{mode}.yml) file:

```yaml
oro_assetic:
    css_debug: [css_group]
```

In order to enable debug mode for all CSS files following configuration can be applied:

```yaml
oro_assetic:
    css_debug_all: true
```

After this configuration was changed cleanup and assets install required:

```php
php app/console cache:clear
php app/console assets:install
```

To get list of all available asset groups next command should be used:

```php
php app/console oro:assetic:groups
```

The next code must be added in main template:

```
    {% oro_css filter='array with filters' output='css/name_of_output_file.css' %}
        <link rel="stylesheet" media="all" href="{{ asset_url }}" />
    {% endoro_css %}
```
These tag is the same as AsseticBundle's "stylesheet" tag but without list of files.
