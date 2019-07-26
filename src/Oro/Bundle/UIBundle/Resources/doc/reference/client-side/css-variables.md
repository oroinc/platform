# CSS Variable Parser

The CSS variable parser reads the current style file and collects the variables in `:root`.

The module returns a promise to which you can connect using the `onReady` method call passing the callback function to the arguments.

You can also listen to the event of a mediator `css:variables:fetched`

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

mediator.on('css:variables:fetched', Foo)

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
