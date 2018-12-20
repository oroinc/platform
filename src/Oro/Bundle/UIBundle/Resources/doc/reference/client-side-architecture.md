Client Side Architecture
========================

 * [Chaplin](#chaplin)
 * [Application](#application)
 * [App Modules](#app-modules)
 * [Page Controller](#page-controller)
 * [Page Component](#page-component)

## Chaplin

Client Side Architecture of OroPlatform is built over [Chaplin](http://chaplinjs.org/) (an architecture for JavaScript web applications based on the [Backbone.js](http://backbonejs.org/) library).

Backbone provides little structure above simple routing, individual models, views and their binding. Chaplin addresses these limitations by providing a light-weight but flexible structure which leverages well-proven design patterns and best practises.

![Chaplin components](http://chaplinjs.org/images/chaplin-lifecycle.png)

See [Chaplin documentation](http://docs.chaplinjs.org/).


## Application
Application gets initialized requiring `oroui/js/app` module on a page (it's required from `OroUIBundle:Default:index.html.twig`):

```
    <script type="text/javascript">
        require(['oroui/js/app']);
    </script>
```

This module exports an instance of `Application` (extend of `Chaplin.Application`), depends on:

- `oroui/js/app/application`, Application class
- `oroui/js/app/routes`, collection of routers
- `oroui/js/app`'s configuration
- and some `app modules` (optional), see [App modules](#app-modules)

### Routes
Routes module (`oroui/js/app/routes`) it's an array with only one route, which matches any URL and refers to `index` method of `controllers/page-controller`:

```
    [
        ['*pathname', 'page#index']
    ]
```

### Application configuration
RequireJS [module configuration](http://requirejs.org/docs/api.html#config-moduleconfig) approach is utilized for passing options to application's constructor. Configuration is placed in `OroUIBundle::requirejs.config.js.twig` template and looks something like:

```
require({
    config: {
        'oroui/js/app': {
            baseUrl: {{ baseUrl|json_encode|raw }},
            headerId: {{ navigationHeader()|json_encode|raw }},
            userName: {{ userName|json_encode|raw }},
            root: {{ rootUrl|json_encode|raw }} + '\/',
            debug: Boolean({{ app.debug }}),
            skipRouting: '[data-nohash=true], .no-hash',
            controllerPath: 'controllers/',
            controllerSuffix: '-controller',
            trailing: null
        }
    }
});
```

It's placed in a twig-template in order to get access to backend variables in runtime. Which is impossible to do in `requirejs.yml` file.


## App Modules
App modules are atomic parts of general application, responsible for:

 * register handlers in `mediator` (see [Chaplin.mediator](http://docs.chaplinjs.org/chaplin.mediator.html));
 * subscribe to `mediator` events;
 * and do all actions which precede creating an instance of application.

App modules export nothing, they are just callback functions that are executed right before the application is started.

App modules are declared in `requirejs.yml` configuration file, in custom section `appmodules`:

```
config:
    appmodules:
        - oroui/js/app/modules/messenger-module
```

This approach allows to define in each bundle code which should be executed on the application start.

### Example
`oroui/js/app/modules/messenger-module` - registers messenger's public methods as handlers in `mediator`

```javascript
define(function(require) {
    'use strict';
    
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');

    /**
     * Init messenger's handlers
     */
    mediator.setHandler('showMessage',
        messenger.notificationMessage, messenger);
    mediator.setHandler('showFlashMessage',
        messenger.notificationFlashMessage, messenger);
    /* ... */
});
```
## Page Layout View

ChaplinJS has introduced [`Chaplin.Layout`](http://docs.chaplinjs.org/chaplin.layout.html) which the top-level application view. 
The view is initialized for the `body` element and stays in memory, even when the active controller is changed. 
We have extended this approach and created `PageLayoutView`. In addition to handling clicks on application-internal links, it collects form data and prepares navigation options for the AJAX POST request.
Also it implements the `ComponentContainer` interface and initializes the top level [`Page Component`](#page-component) defined in the page's HTML. 
That allows to create the so called global views. These views stay in the memory, as well as `PageLayoutView`, when the active controller is changed.

## Page Controller

The Page Controller is the central part of platform's architecture. After [Chaplin.Dispatcher](http://docs.chaplinjs.org/chaplin.dispatcher.html) calls the target method (which is `page#index`), the Page Controller executes the whole stack of page loading and triggers proper events at each stage. The Page Model (`oroui/js/app/models/page-model`) is used as a  container for page's data and performs interactions with the server (loads data on navigation, posts data on form submit). Page Controller also works with pages cache component and declares some [navigation handlers](./mediator-handlers.md#page-controller).

![Page loading flow](./page-controller.png)

### Events

#### Page loading stages

Event Name | Arguments
---------- | ---------
`'page:beforeChange'` | `oldRoute`, `newRoute`, `options` (can be triggered with no arguments)
`'page:request'` | `actionArgs`
`'page:update'` | `pageData`, `actionArgs`, `jqXHR`, `updatePromises`
`'page:afterChange'` | no arguments
`'page:redirect'` | no arguments

#### Page error handling

Event Name | Arguments
---------- | ---------
`'page:beforeError'` | `jqXHR`, `payload`
`'page:error'` | `pageData`, `actionArgs`, `jqXHR`

## Page Component
Because our appproach is a "Blocks-Driven" application (meaning that one controller for all routes and loaded page-content consists of self-sufficient blocks), we introduce the new kind of entity that takes responsibility for initializing views, binding them with app's environment and disposing them at the appropriate time. Basically it performs the job of the controller and any page may contain mulitple components like this.

For additional details please read [Page Component](./page-component.md).
