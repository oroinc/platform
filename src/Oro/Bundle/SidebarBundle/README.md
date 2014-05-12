OroSidebarBundle
================

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

## Widget configuration in YAML
Default data for your widget should be defined in `widget.yml` file in
`/Resources/public/sidebar_widget/widget_name/widget.yml`. This file can contains following item settings:

* **title** - title text of your widget;
* **iconClass** - css icon class from `Font Awesome` icons. When this property is set, then **icon** setting will be ignored;
* **icon** - path to the icon image of your widget in the assets folder;
* **cssClass** - css class for the container of your widget;
* **module** - alias of the path to your widget in the asset folder, which should be declare in the `require.yml` file;
* **placement** - possible sides placement for your widget. Available positions: `right`, `left`, `both`;
* **settings** - custom settings of your widget;

Example:

```yml
title:     "Task list"
iconClass: "icon-list-alt"
module:    "orocrm/sidebar/widget/assigned_tasks"
placement: "both"
cssClass:  "sidebar-widget-assigned-tasks"
settings:
    perPage: 5
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
