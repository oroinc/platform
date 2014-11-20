Context Calendar Menu
=====================

Table of content
-----------------
- [Overview](#overview)
- [Extendability](#extendability)

##Overview
Each item is calendar in left sidebar on page "My Calendar". Each of them can have context menu. You can see it if click
 on three dots icon in right side of calendar item. Context menu have actions. By default we have actions "Show/Hide
 calendar", "Remove calendar" (from calendar items list) and "Choose calendar color".

##Extendability
Calendar Context Menu is based on knplabs/knp-menu. The menu can be extended with new action items from any bundle.
 To add a new action to the menu, the action configuration must be added to the navigation.ym, for example:
``` yaml
oro_menu_config:
    items:
        oro_calendar_remove_action:
            label: 'oro.calendar.context.remove'
            uri: '#'
            extras:
                position: 20
                module: 'orocalendar/js/calendar/menu/remove-calendar'
                template: 'OroCalendarBundle:Calendar:Menu/removeCalendar.html.twig'
    tree:
        calendar_menu:
            type: calendar_menu
            children:
                oro_calendar_remove_action: ~
```

Developer may define attributes:
- **Label** shows action name into menu (required).
- **Position** define place of action in context menu.
- **Module** consists js module that performs action into browser. If the developer does not provide the "module"
 attribute, the context menu will not contain this action.
- **Template** provides twig template that replace standard item template, for example:
``` twig
<li{{ oro_menu.attributes(itemAttributes) }}>
    <a href="javascript:void(0);" class="action">
    <% if (visible) { %>
        {{ 'oro.calendar.context.hide'|trans }}
    <% } else { %>
        {{ 'oro.calendar.context.show'|trans }}
    <% } %>
    </a>
</li>
```
In a template developer should provide ```<li{{ oro_menu.attributes(itemAttributes) }}>``` tag. Also he can do
 not provide own template and uses standard template.

**Module** received next values from **options** into **initialize**:
- **el** - context menu item
- **model** - a Backbone model represents [a calendar connection](../public/js/calendar/connection/model.js)
- **collection** - a Backbone [collection](../public/js/calendar/connection/collection.js) of calendar item models
- **colorManager** - [manager](../public/js/calendar/color-manager.js) that consist functions and list of predefined and
 used colors for calendar
- **connectionsView** - a Backbone view represents [a calendar items list](../public/js/calendar/connection/view.js)
- **closeContextMenu** - a function that closes context menu

In js module developer can use default method **execute**. This method will call when user click on your item menu, for
 example:
``` js
...
execute: function (model, actionSyncObject) {
    var removingMsg = messenger.notificationMessage('warning', __('Removing the calendar, please wait ...')),
        $connection = this.connectionsView.findItem(model);
    try {
        $connection.hide();
        model.destroy({
            wait: true,
            success: _.bind(function () {
                removingMsg.close();
                messenger.notificationFlashMessage('success', __('The calendar was removed.'));
                actionSyncObject.resolve();
            }, this),
            error: _.bind(function (model, response) {
                removingMsg.close();
                this._showError(__('Sorry, the calendar removing was failed'), response.responseJSON || {});
                $connection.show();
                actionSyncObject.reject();
            }, this)
        });
    } catch (err) {
        removingMsg.close();
        this._showError(__('Sorry, unexpected error was occurred'), err);
        $connection.show();
        this.actionSyncObject.reject();
    }
},
...
```
**Execute()** function must receive two parameters:
- **model** is [a Backbone model](../public/js/calendar/connection/model.js) that consist property of calendar item.
- **actionSyncObject** is jQuery deferred object that synchronize context menu action and rendering calendar. If custom
 action done successfully then promise object should perform **resolve()** function. In other case should perform
 **reject()** method.

Another case developer can use any other js events or functions to start context menu action in module. In this case module
 should execute method **_initActionSyncObject()** of object **connectionsView** before start action, that user can't run
 another menu actions. After action was done module should execute **resolve()** or **reject()** functions of object
 **connectionsView._actionSyncObject**.

Don't forgot to execute cache:clear after update navigation.yml, and assets:install and assetic:dump for dev environment
 and oro:requirejs:build for production environment.
