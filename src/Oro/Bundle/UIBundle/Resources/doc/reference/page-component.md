Page Component
==============

 * [Intro](#intro)
 * [Definition](#definition)
 * [How it works](#definition)
 * [Development](#development)

## Intro
Page Component is an invisible component that takes responsibility of the controller for certain functionality. It accepts options object, performs initialization actions, and, at appropriate time, destroys initialized elements (views, models, collections, or even sub-components).

## Definition
To define PageComponent for a block define two data-attributes for the HTML node:

 - `data-page-component-module` with the name of the module
 - `data-page-component-options` and with safe JSON-string

```twig
{% set options  = {
    metadata: metaData,
    data: data
} %}
<div data-page-component-module="mybundle/js/app/components/grid-component"
     data-page-component-options="{{ options|json_encode }}"></div>
```

## How it works

`PageController` loads a page, triggering `'page:update'` event. Once all global views have updated their content, `PageController` executes `'layout:init'` handler. This handler performs an action for the container it passes (`document.body` in this case), one of such actions is `initPageComponents`. The method responsible for this action:

 - collects all elements with proper data-attributes.
 - loads defined modules of PageComponents.
 - initializes PageComponents, executing init method with passed-in options.
 - once all components are initialized, resolves `initialization` promise with passed array of components.

`PageController` handles this promise and attaches all received components to itself in order to dispose them once controller got disposed.

## Development
There are two kinds of PageComponents:

 - an extension of `BaseComponent`.
 - just a function for trivial cases.

### Extension of BaseComponent
BaseComponent is a module ['oroui/js/app/components/base/component'](../../public/js/app/components/base/component.js) very similar to `Backbone.View`. The difference is that it is not visible and has no functionality to interact with DOM.

It has two static methods:

 - `extend`, the same as other `Backbone` components have.
 - `init`, that accepts options and creates an instance of the current component. If the component instance has no `defer` property (meaning tha component has been created synchronously) `init` method returns this component. If it has `defer` property, `init` method returns a promise object. Once the component get initialized, it should resolve `defer` with its instance.

An instance of BaseComponent has two methods:

 - `initialize`, that accepts options and performs initialization;
 - `dispose`, that besides removing all kind of event subscriptions like all `Chaplin` components do goes though `subComponents` property (that is an array), disposes all its sub-components, and then tries to call `dispose` method for all other properties.

```javascript
MyComponent = BaseComponent.extend({
    initialize: function (options) {
        options = options || {};
        this.processOptions(options);
        if (!_.isEmpty(options.modules)) {
            // defer object, means myView will be init in async way
            this.defer = $.Deferred();
            // there are some modules we need to load before init view
            tools.loadModules(options.modules, function (modules) {
                _.extend(options.viewOptions, modules);
                this.initView(options.viewOptions);
                // resolves defer once view get initialized
                this.defer.resolve(this);
            }, this);
        } else {
            // initialize view immediately
            this.initView(options);
        }
    },
    processOptions: function (options) {
        /* manipulate options, merge them with defaults, etc. */
    },
    initView: function (options) {
        this.view = new MyView(options);
    }
});
```


### Function as a Component
For some trivial cases writing the entire component as extension from `BaseComponent` is redundant. This is definitely the case if you don't need to:

 - dispose this component (the result is self-disposable), or
 - extend the component for other cases.

In this case it's better to define a function that accepts options and performs the initialization:

```javascript
define(['jquery', 'js/my-widget'], function ($) {
    return function (options) {
        $(options.el).myWidget(options.widgetOptions);
    };
}
```
