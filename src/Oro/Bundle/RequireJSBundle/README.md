OroRequireJSBundle
====================
This bundle provides easy way to:

 -  generates require.js config file for a project;
 -  optimizes, minify and merge all JS-file into one resources.

For details of configuration options see [RequireJS API].<br />
For details of build options see [example.build.js].

## Require.js config generation
### Configuration
Common options for require.js config are placed in ```app/config.yml```:

    oro_require_js:
        config: # common options which will eventually get into require.js config file
            waitSeconds: 0
            enforceDefine: true
            scriptType: 'text/javascript'

Bundle specific options are defined inside ```requirejs.yml``` file, which is placed in ```%BundleName%\Resources\config\requirejs.yml```.
It can have three sections ```shim```, ```map``` and ```paths``` (see [RequireJS API]).
Each bundle's javascript module have to be defined in ```paths``` section, where key is a module name and value is its relative path from document root.

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
Main require.js config is generated automatically and embedded in HTML-page. The config is stored in application cache. So if you want, for some reason, renew a require.js configuration, then just clean a cache.

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

Sometimes it is necessary to modify require.js configuration on a fly (e.g. to set dynamic path in depends of request parameters etc.).
It is possible to do over `config_extend` parameter for `OroRequireJSBundle::scripts.html.twig` template.
That variable can contain piece of custom configuration which will be applied after general configuration is loaded and before any module get utilized.

E.g. to dynamically define path to translation dictionary (depending on what locale is currently used):

    {% set requirejs_config_extend %}
        require({
            paths: {
                'oro/translations':
                    '{{ url('oro_translation_jstranslation')[0:-3] }}'
            }
        });
    {% endset %}


In terms of sequence of code execution it looks:

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
Build configuration starts in ```app/config.yml```

    oro_require_js:
        build_path: "js/oro.min.js"     # relative path from document root folder to project built
        building_timeout: 3600
        js_engine: "node"               # can be configured to use other engine, e.g. Rhino
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

    php app/console oro:requirejs:build

It will:

1. take initial configuration from ```oro_require_js.build``` (```app/config.yml```);
1. extend it with configuration found in bundles (```%BundleName%\Resources\config\requirejs.yml```);
1. generate ```build.js``` - a config for builder;
1. run builder (time consuming process, especially for Rhino JS-engine);
1. remove ```build.js```.

[RequireJS API]: <http://requirejs.org/docs/api.html#config>
[example.build.js]: <https://github.com/jrburke/r.js/blob/master/build/example.build.js>
