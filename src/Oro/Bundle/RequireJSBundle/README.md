# OroRequireJSBundle

OroRequireJSBundle uses the [RequireJS](http://requirejs.org/) library to enable a modular structure of JS components in Oro applications.

The bundle enables developers to define RequireJS configuration in YAML files on the bundle level. It also provides a CLI tool to collect RequireJS modules and configuration from bundles, merge and minify them in the production mode.

For details of the RequireJS configuration options, see [RequireJS API].
For details of the RequireJS build options, see [example.build.js].

## Require.js config generation
### Configuration
Common options for require.js config are placed in ```config.yml```:

    oro_require_js:
        config: # common options which will eventually get into require.js config file
            waitSeconds: 0
            enforceDefine: true
            scriptType: 'text/javascript'

Bundle specific options are defined inside ```requirejs.yml``` file, which is placed in ```%BundleName%\Resources\config\requirejs.yml```.
It can have three sections ```shim```, ```map``` and ```paths``` (see [RequireJS API]).
Each bundle's javascript module have to be defined in ```paths``` section, where key is a module name and value is its relative path from the document root.

    config:
        shim:
            'jquery-ui':
                deps:
                    - 'jquery'
        map:
            '*':
                'jquery': 'oroui/js/jquery-extend'
            'oroui/js/jquery-extend':
                'jquery': 'jquery'
        paths:
            'jquery': 'bundles/oroui/lib/jquery-1.10.2.js'
            'jquery-ui': 'bundles/oroui/lib/jquery-ui.min.js'
            'oroui/js/jquery-extend': 'bundles/oroui/js/jquery-extend.js'

### Generation
Main require.js config is generated automatically and embedded in HTML-page. The config is stored in application cache. In case you wish to renew require.js configuration, just clean cache.

### Usage
To get `require.js` script with its configuration on your page, just include `scripts.html.twig` template from `OroRequireJSBundle` to `<head/>` tag of your `index.html.twig` template.

    <head>
        <!-- -->
        {% include 'OroRequireJSBundle::scripts.html.twig' %}
        <!-- -->
    </head>




The template `scripts.html.twig` accepts two optional parameters `compressed` and `config_extend`.

- `compressed` is boolean (`true` by default), determines whether to use minified js-file or not. Usually it's opposite to `app.dev` flag.
- `config_extend` is a string with javascript code, allows to extend requirejs configuration in runtime mode (see [runtime require.js config](#runtime-requirejs-main-config-extension)).


    {% set requirejs_config_extend %}
        // custom javascript code
    {% endset %}
    {% include 'OroRequireJSBundle::scripts.html.twig' with {
        compressed: not app.debug,
        config_extend: requirejs_config_extend
    } %}


## Runtime require.js main config extension

Sometimes it is not enough to specify require.js configuration settings statically, it is required to modify certain parameters dynamically at each launch. It is possible to do this over `config_extend` parameter for `OroRequireJSBundle::scripts.html.twig` template.
That variable can contain a piece of custom configuration which will be applied after general configuration is loaded and before any module is utilized.

E.g. to dynamically define the path to translation dictionary (depending on what locale is currently used):

    {% set requirejs_config_extend %}
        require({
            paths: {
                'oro/translations':
                    '{{ url('oro_translation_jstranslation')[0:-3] }}'
            }
        });
    {% endset %}


In terms of sequence of code execution, it looks the following way:

 1. Prod mode (and built resource exists)
    - execute all custom configurations<br />
    ```require(/* ... */); require(/* ... */); require(/* ... */);```
    - load single minified js-resource (with ```require-config.js``` + ```require.js``` and rest of modules)
 1. Dev mode (or built resource does not exist)
    - load ```require.js```
    - load ```js/require-config.js```
    - execute all custom configurations<br />
    ```require(/* ... */); require(/* ... */); require(/* ... */);```

See ```@OroRequireJSBundle::scripts.html.twig```

## Build project
### Configuration
Build configuration starts in ```config.yml```

    oro_require_js:
        build_path: "js/oro.min.js"     # relative path from document root folder to project built
        building_timeout: 3600
        build_logger: false             # show in browser console not optimized RequireJS modules 
        build:                          # build.js's common options
            optimize: "uglify2"
            preserveLicenseComments: true
            generateSourceMaps: true
            useSourceUrl: true

See details for [```oro_require_js.build```][example.build.js] options.

Beside general build-configuration, you can set bundle specific options inside ```%BundleName%\Resources\config\requirejs.yml``` file, root section ```build```.

    build:
        paths:
            'autobahn': 'empty:'

This directive will prevent module from getting concatenated into build file.

### Building
To make a build for JS-resources, just execute a command in console:

    php bin/console oro:requirejs:build

It will:

1. take initial configuration from ```oro_require_js.build``` (```config.yml```);
1. extend it with configuration found in bundles (```%BundleName%\Resources\config\requirejs.yml```);
1. generate ```build.js``` - a config for builder;
1. run builder (time consuming process, especially for Rhino JS-engine);
1. remove ```build.js```.

[RequireJS API]: <http://requirejs.org/docs/api.html#config>
[example.build.js]: <https://github.com/jrburke/r.js/blob/master/build/example.build.js>
