Sidebars
========
Sidebars layout and Widget containers

## Sidebar initializing
`left_panel.html.twig` and `right_panel.html.twig` templates define initial html markup and data
for left and right sidebars accordingly. The `data-models` attribute with JSON-data is expected
to contain data for sidebar model. Example:

```js
sidebar: {
    widgets: [
        // array of all registered widgets
        {
            title: 'Hello world',
            icon: 'bundles/orosidebar/img/hello-world.ico',
            module: 'orosidebar/widget/hello-world'
        }
    ]
},
widgets: [
    // widget instances, hosted on the sidebar
    {
        id: 1,
        title: 'Hello world',
        icon: 'bundles/orosidebar/img/hello-world.ico',
        module: 'orosidebar/widget/hello-world',
        settings: {
            content: 'Hello, World!!!'
        }
    }
]
```

## Creating Widgets
The widget is a module, exporting 3 entities: default settings, ContentView and SetupView.
ContentView and SetupView are Backbonejs views. defaults is template for widget's settings. Example:

```js
defaults: {
    title: 'Hello world',
    icon: 'bundles/orosidebar/img/hello-world.ico',
    settings: function () {
        return {
            content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse pulvinar.'
        };
    }
}
```
    
The bundle includes example widget, called `helloWorld`, located at `bundles/orosidebar/js/widget/widget.js`
