# CSS Variable Parser

For collect global CSS variables we use [css-vars-ponyfill](https://github.com/jhildenbiddle/css-vars-ponyfill)

The CSS variable parser reads the current style file and collects all global css variables from `:root`.

The module returns a promise which you can connect using the `onReady` method call passing the callback function to the arguments.

You can also listen the event via mediator `css:variables:fetched`

### Default options

```javascript
{
    onlyLegacy: false,
    preserveStatic: false,
    updateDOM: false,
    updateURLs: false
}
```
Internet Explorer doesn't support global CSS variables. For enable support for IE set `updateDOM: true`

Insert in twig file
```twig
{% import '@OroAsset/Asset.html.twig' as Asset %}
{{ Asset.js_modules_config({
    'oroui/js/app/modules/css-variable-module': {
        'updateDOM': true
    }
}); }}

```

For more information navigate to official plugin [documentation](https://jhildenbiddle.github.io/css-vars-ponyfill)

Usage examples:

Set some variables in SCSS:
```scss
:root {
    --some-var: #000000;
}
```

How to use onReady callback function:
```javascript

var cssVariablesManager = require('oroui/js/css-variables-manager');

var Foo = function(cssVariables) {
    
    var someVar = cssVariables['--some-var'];
    
    console.log(someVar); // variable value "#000000"
}

cssVariablesManager.onReady(Foo);

```

How to use the mediator:
```javascript

var mediator = require('oroui/js/mediator');

var Foo = function(cssVariables) {
    
    var someVar = cssVariables['--some-var'];
    
    console.log(someVar); // variable value "#000000"
}

mediator.on('css:variables:fetched', Foo);

```

How to use the getter method:
```javascript

var cssVariablesManager = require('oroui/js/css-variables-manager');

var Foo = function(cssVariables) {
    
    var someVar = cssVariables['--some-var'];
    
    console.log(someVar); // variable value "#000000"
}

setTimeout(function() {
    
    var variables = cssVariablesManager.getVariables();
    
    Foo(variables);
}, 10000);
```

SCSS breakpoints you can listen via mediator `css:breakpoints:fetched`
