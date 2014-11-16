Context Calendar Menu
=====================

Table of content
-----------------
- [Overview](#overview)
- [Extendability](#extendability)

##Overview
Each item is calendar in left sidebar on page "My Calendar". Each of them can have context menu. You can see it if click
 on three dots icon in right side of calendar item. Context menu have actions. By default we have two actions "Show/Hide
 calendar" and "Remove calendar" (from calendar items list).

##Extendability
Developer can extend calendar item context menu. He can add his own action to menu. Developer can add new action into
 any bundle. Context Menu based on knplabs/knp-menu. First of all developer should add menu item into navigation.yml, for
 example:
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

Developer must define parameters:
- Label shows action name into menu.
- Module consists js module that to perform action into browser. If developer don't provide module parameter then
 context menu don't have this action.
- Template parameter provide twig template that replace standard item template? for example:
``` twig
<% if (removable) { %>
    {% if item.extras.module is defined %}
        {% set itemAttributes = itemAttributes|merge({'data-module': item.extras.module })%}
    {% endif %}
    <a href="javascript:void(0);"{{ oro_menu.attributes(itemAttributes) }}>{{ label }}</a>
<% } %>
```

In js module developer should done method execute. This method will call when user click on your item menu. Example:
``` js
...
execute: function (model, promise) {
    var removingMsg = messenger.notificationMessage('warning', __('Removing the calendar, please wait ...')),
        $connection = this.connectionsView.findItem(model);
    try {
        $connection.hide();
        model.destroy({
            wait: true,
            success: _.bind(function () {
                removingMsg.close();
                messenger.notificationFlashMessage('success', __('The calendar was removed.'));
                promise.resolve();
            }, this),
            error: _.bind(function (model, response) {
                removingMsg.close();
                this._showError(__('Sorry, the calendar removing was failed'), response.responseJSON || {});
                $connection.show();
                promise.reject();
            }, this)
        });
    } catch (err) {
        removingMsg.close();
        this._showError(__('Sorry, unexpected error was occurred'), err);
        $connection.show();
        promise.reject();
    }
},
...
```

Execute function must receive two parameters:
- model is Backbone object that consist property of calendar item.
- promise is jQuery deferred object that synchronize context menu action and rendering calendar. If custom action done
 successfully then promise object should perform resolve() method. In other case should perform reject() method.

Don't forgot to execute cache:clear after update navigation.yml, and assets:install and assetic:dump for dev environment
 and oro:requirejs:build for production environment.
