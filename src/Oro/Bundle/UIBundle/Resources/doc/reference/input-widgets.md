**input widget** - is any widget used for form elements, such as: datepicker, uniform, select2, etc...

**\*InputWidget** is used to provide a common API for all input widgets.
By using this API you provide ability to change input widget to any other or remove it, without changes is code, that interacts with widget.

**InputWidgetManager** is used to register input widgets and create widget for applicable inputs.

**$.fn.inputWidget** - is an jQuery API for InputWidgetManager or InputWidget.

Example of usage:
-------------------

```javascript
//Create new input widget

var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
var UniformSelectInputWidget = AbstractInputWidget.extend({
    widgetFunctionName: 'uniform',
    refreshOptions: 'update',
    containerClassSuffix: 'select',

    dispose: function() {
        if (this.disposed) {
            return;
        }
        this.$el.uniform.restore();
        UniformSelectInputWidget.__super__.dispose.apply(this, arguments);
    },

    findContainer: function() {
        return this.$el.parent('.selector');
    }
});

//Register widget in InputWidgetManager

var InputWidgetManager = require('oroui/js/input-widget-manager');
InputWidgetManager.addWidget('uniform-select', {
    selector: 'select:not(.no-uniform)',
    Widget: UniformSelectInputWidget
});

//Create widgets for all apllicable inputs

$(':input').inputWidget('create');

/**
* Call function from InputWidget or jQuery.
* See available functions in AbstractInputWidget.overrideJqueryMethods
* Example: will be executed InputWidget.val function, if widget and function exists, or $.val function.
*/
$(':input').inputWidget('val', newValue);
```

Your can see more examples in code:

`InputWidgetManager` and `$.fn.inputWidget` with examples in comments: [`oroui/js/input-widget-manager.js`](../../public/js/input-widget-manager.js)

`AbstractInputWidget`: [`oroui/js/app/views/input-widget/abstract`](../../public/js/app/views/input-widget/abstract.js)

`UniformSelectInputWidget`: [`oroui/js/app/views/input-widget/uniform-select`](../../public/js/app/views/input-widget/uniform-select.js)

`UniformFileInputWidget`: [`oroui/js/app/views/input-widget/uniform-file`](../../public/js/app/views/input-widget/uniform-file.js)

Register `UniformSelectInputWidget` and `UniformFileInputWidget` in `InputWidgetManager`: [`oroui/js/app/modules/input-widgets`](../../public/js/app/modules/input-widgets.js)
