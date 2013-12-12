OroThemeBundle
==============

This bundle provides basic themes support.

Basic statements
----------------
* Bundle can provide CSS and templates that are required for it's UI;
* UI of bundles (css and templates markup) should be "themable", it means that CSS and UI components of bundle should
  be affectable by theme to change look and feel of application (at least colors and icons);
* A theme is a set of CSS/LESS files that will be included at the end of all CSS files to cascade styles and change look and feel;

Theme Properties
----------------

Each theme can have next properties:

* **name**
_required_
Unique name of the theme.

* **label**
_optional_
This will be displayed in theme management UI.

* **styles**
_required_
The list of CSS and LESS files that represent a theme.

* **icon**
_optional_
Standard "favicon.ico" file for this theme.

* **logo**
_optional_
Logo that will be displayed in the UI.

* **screenshot**
_optional_
This be displayed in theme management UI.

Adding a theme using app/config.yml
-----------------------------------

To add a theme you can use _app/config.yml_ file.

```
# add to config.yml next string to use mytheme theme
oro_theme:
    active_theme: mytheme
    themes:
        mytheme:
            styles:
                - mytheme/css/main.css
                - mytheme/css/ie.css
            label: My Theme
            icon: mytheme/images/favicon.ico
            logo: mytheme/images/logo.png
            screenshot: /mytheme/images/screenshot.png
```

Make sure that your root public sites directory (generally a "web" directory in symfony) contains mytheme directory
with all used files.

Adding a theme using a bundle
-----------------------------

Theme could be added in any bundle, place a file in _Resources/public/theme/<theme_name>/settings.yml path inside of bundle.
This file contains same configuration like in app/config.yml:

```
styles:
    - bundles/mybundle/themes/mytheme/css/main.css
    - bundles/mybundle/themes/mytheme/css/ie.css
label: My Theme
icon: bundles/mybundle/themes/mytheme/images/favicon.ico
logo: bundles/mybundle/themes/mytheme/images/logo.png
screenshot: bundles/mybundle/themes/mytheme/images/screenshot.png
```

Overriding a theme
------------------

All themes settings are collected and merged at compile time of DI container. Bundle could override others bundle theme
by placing a file with theme _Resources/public/theme/<theme_name>/settings.yml in settings.yml path.


Loading styles of theme
-----------------------

When application has active theme it's styles append to the end of the list of all CSS assets of bundles. Theme's styles
will override existing bundles styles to change look and feel of application.

To set active theme add next settings to _app/config.yml_

```
oro_theme:
    active_theme: <theme_name>
```

After this change was made next commends should be executed:

```
app/console cache:clear
app/console assets:install
app/console assetic:dump
```


Debugging theme styles:
-----------------------

Each theme is appended to the list of OroAsseticBundle's CSS assets in group "theme". So, if you want to debug theme's
styles, you should use next configuration in _app/config.yml_:

```
oro_assetic:
    css_debug: [theme]
    # css_debug_all: true # if you want to debug all CSS assets
```

When you are making changes to theme's CSS don't forget to run next commands:

```
app/console cache:clear # if you have changed some theme's setting, including adding/removing CSS/LESS styles files.
app/console assets:install # if you have changed themes files, you can use --symlink parameter, in this case you should install it only once
app/console assetic:dump # if you are not using oro_assetic.css_debug: [theme] or oro_assetic.css_debug_all: true options
```

Getting list of all available themes:
-------------------------------------

There is a command _oro:theme:list_ for this purpose. Here is an example output of this command:

```
List of available themes:
oro (active)
 - label: Oro Theme
 - icon: bundles/oroui/themes/oro/favicon.ico
 - styles: bundles/oroui/themes/oro/style.css
demo (active)
 - label: Demo Theme
 - logo: bundles/oroui/themes/demo/images/logo.png
 - icon: bundles/oroui/themes/demo/images/favicon.ico
 - styles: bundles/oroui/themes/demo/css/main.less
```

