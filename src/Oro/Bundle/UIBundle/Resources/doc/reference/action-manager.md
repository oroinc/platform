Action Manager
==============

It allows you to add actions globally for all jsTree in the application, in one place

**ActionManager.addAction(name, action)** method takes two arguments {name, action}
    'name' - unique action identifier
    'action' - object with view instance and hook property, when this property contain *true*, action will be append to tree view

    {
        view: 'path/to/some-action-view',
        hook: 'someProperty'
    }
or hook parameter can get multiple properties

    {
        view: 'path/to/some-action-view',
        hook: {
            someProperty: true,
            someProperty2: 'string' or 'number'
        }
    }

Example of usage:
-------------------

```javascript

// Create action

    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');

    SomeActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'custom-icon',
            label: 'Custom Label'
        }),


        onClick: function() {

            // Get jstree instance

            var $tree = this.options.$tree;

            // Get jstree settings

            var settings = $tree.jstree().settings;

            // Add here action functionality
        }
    });

    return SomeActionView;

// Register new action


    var ActionManager = require('oroui/js/jstree-action-manager');
    var SomeActionView_1 = require('oroui/js/app/views/jstree/some-action-view-1');
    var SomeActionView_2 = require('oroui/js/app/views/jstree/some-action-view-2');

    ActionManager.addAction('subtree', {
        view: SomeActionView_1,
        hook: 'someProperty'
    });

    ActionManager.addAction('subtree', {
        view: SomeActionView_2,
        hook: {
            someProperty: true,
            someProperty2: 'some string'
        }
    });
```
Your can see more examples in code:

`ActionManager.addAction` with examples in comments: [`oroui/js/app/modules/jstree-actions-module.js`](../../public/js/app/modules/jstree-actions-module.j)
