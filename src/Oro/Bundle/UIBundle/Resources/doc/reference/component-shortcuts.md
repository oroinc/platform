Component Shortcuts
===================

Provides ability to use simple widgets/modules without complicated data-page-component-* attributes. `ComponentManager`
will handle initialization of all registered shortcuts.

Component options could be defined in three different ways:

###### Empty value

Example:

```html
    <div data-page-component-collapse>
        Some content to collapse
    </div>
```

```javascript
    // register shortcuts in ComponentShortcutsManager
    var ComponentShortcutsManager = require('oroui/js/component-shortcuts-manager');

    ComponentShortcutsManager.add('collapse', {
        moduleName: 'oroui/js/app/components/jquery-widget-component',
        options: {
            widgetModule: 'oroui/js/widget/collapse-widget'
        }
    });
```

###### Object value

Example:

```html
    <div data-page-component-collapse="{{ {storageKey: 'entityWorkflow' ~ entityId}|json_encode }}">
        Some content to collapse
    </div>
```

```javascript
    // register shortcuts in ComponentShortcutsManager
    var ComponentShortcutsManager = require('oroui/js/component-shortcuts-manager');

    ComponentShortcutsManager.add('collapse', {
        moduleName: 'oroui/js/app/components/jquery-widget-component',
        options: {
            widgetModule: 'oroui/js/widget/collapse-widget'
        }
    });
```

###### Scalar value (remember to add scalarOption to shortcut)

Example:

```html
    <div data-page-component-jquery="oroui/js/widget/collapse-widget">
        Some content to collapse
    </div>
```

```javascript
    // register shortcuts in ComponentShortcutsManager
    var ComponentShortcutsManager = require('oroui/js/component-shortcuts-manager');

    ComponentShortcutsManager.add('jquery', {
        moduleName: 'oroui/js/app/components/jquery-widget-component',
        scalarOption: 'widgetModule'
    });
```

### ComponentShortcutsManager

Is used to register shortcuts. Also containing helper method `getComponentData` to generate data for component initialization  
by element attributes and shortcut config.

### References:

[ComponentShortcutManager](../../public/js/component-shortcuts-manager.js)

[ComponentShortcutsModule](../../public/js/app/modules/component-shortcuts-module.js)
