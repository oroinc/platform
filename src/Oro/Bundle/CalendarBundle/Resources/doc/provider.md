Calendar Provider
=================

Table of content
-----------------
- [Overview](#overview)
- [Getting Started](#getting-started)
- [Implementation](#implementation)

##Overview
Calendar Provider gives developers access to the formation of an user calendar. The Calendar Manager consist all
 Calendar Providers. Manager generates a list of calendars that will be displayed on the page "My Calendar".

##Getting Started
You must create Calendar Provider class that implements CalendarProviderInterface. To register this class as service
 with tag "oro_calendar.calendar_provider" and define alias name, for example: "user".
``` yaml
oro_calendar.calendar_provider.user:
    class: %oro_calendar.calendar_provider.user.class%
    arguments:
        - @oro_entity.doctrine_helper
        - @oro_locale.formatter.name
        - @oro_calendar.calendar_event.normalizer
    tags:
        - { name: oro_calendar.calendar_provider, alias: user }
```

##Implementation
You must provide two methods getCalendarDefaultValues and getCalendarDefaultValues.

getCalendarDefaultValues($userId, $calendarId, array $calendarIds), where:
- $userId - the id of an user requested this information (type: integer),
- $calendarId - the target calendar id (type: integer),
- $calendarIds - the list of ids of connected calendars (type: array of integer).

Method return array of default properties for the given calendar. Each item of this array can contains any properties of
 a calendar you need to set as default. You can return any property defined in CalendarProperty class. If you need extra
 properties you can return them in 'options' array. There are several additional properties you can return as well:
- calendarName - a name of a calendar. This property is mandatory,
- removable - indicated whether a calendar can be disconnected from the target calendar defaults to true.

Also there is special property names 'options' where you can return some additional options. For example:
- widgetRoute - route name of a widget can be used to view an event. defaults to empty,
- widgetOptions - options of a widget can be used to view an event. defaults to empty.

You can add additional fields to entity CalendarProperty via UI or Source Code. So you can provide default values for
 this fields into calendar provider. If value of this fields will be changed then system will store it.

getCalendarDefaultValues($userId, $calendarId, array $calendarIds), where:
- $userId - the id of an user requested this information (type: int),
- $calendarId - the target calendar id (type: int),
- $start - a date/time specifies the begin of a time interval (type: DateTime),
- $end - a date/time specifies the end of a time interval  (type: DateTime),
- $subordinate - determines whether events from connected calendars should be included or not (type: bool).

Method return array of calendar events. Each item of this array should contains all properties of a calendar event. There
 are several additional properties you can return as well:
- editable  - indicated whether an event can be modified. defaults to true,
- removable - indicated whether an event can be deleted. defaults to true,
- reminders - the list of attached reminders. defaults to empty.
