OroThemeBundle
==============

This bundle provides basic themes support.

Basic statements
----------------
* bundle can provide CSS and templates that are required for UI;
* CSS resources of bundle should be connected using assets.yml file;
* UI bundles (for example OroUIBundle, PimUIBundle, OroCRMUIBundle) can contain CSS and templates that can be reused in other bundles;
* UI of bundles (css and templates markup) should be themable, it means that theme should affect look and feel (at least colors and icons);
* BAP provides a basic theme support;
* A theme is a set of CSS/LESS files that will be included at the end of all CSS files to cascade styles and change look and feel;
*

Theme Properties
----------------

Each theme can have next properties:

* **name**
_unique_

* **label**
_optional_
This will be displayed in theme management UI.

* **styles**
_required_
The list of CSS and LESS files that represents a theme.

* **icon**
_optional_
Standard "favicon.ico" file for this theme.

* **logo**
_optional_
Logo that will be displayed in UI.

* **screenshot**
_optional_
This  be displayed in theme management UI.

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
                - /mytheme/css/main.css
                - /mytheme/css/ie.css
            label: My Theme
            icon: /mytheme/images/favicon.ico
            logo: /mytheme/images/logo.png
            screenshot: /mytheme/images/screenshot.png
```

Make sure that your root public sites directory (generally a "web" directory in symfony) contains mytheme directory
with all used files.

Adding a theme using a bundle
-----------------------------

Theme could be added in any bundle, place settings.yml next file in _Resources/public/theme/<theme_name>_ directory of bundle.
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
