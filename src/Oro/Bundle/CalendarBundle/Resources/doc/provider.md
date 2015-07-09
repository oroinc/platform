Calendar Provider
=================

Table of content
-----------------
- [Overview](#overview)
- [Add own provider](#add-own-provider)

##Overview

The goal of calendar providers is to allow developers to add different types of calendars on user's calendar. The main class responsible to work with calendar providers is [Calendar Manager](../../Manager/CalendarManager.php). This class contains all providers and responsible to collect and merge data from them.

##Add own provider

To add a calendar provider you need to create a class implements [CalendarProviderInterface](../../Provider/CalendarProviderInterface.php), register it as a service and mark it with *oro_calendar.calendar_provider* tag. Each provider must have an alias that is unique identifier of a provider. The following example shows how calendar provider can be registered:

``` yaml
oro_calendar.calendar_provider.user:
    class: %oro_calendar.calendar_provider.user.class%
    arguments:
        - @oro_entity.doctrine_helper
        - @oro_entity.entity_name_resolver
        - @oro_calendar.calendar_event_normalizer.user
    tags:
        - { name: oro_calendar.calendar_provider, alias: user }
```

As it mentioned below your provider must implements [CalendarProviderInterface](../../Provider/CalendarProviderInterface.php) which contains only two methods:

- **getCalendarDefaultValues** - This method returns default values of a calendar properties, such as calendar name, permissions, widget optionsm etc.
- **getCalendarEvents** - This method returns a list of calendar events.

More details about implementation of a calendar provider you can find in [source code](../../Provider/CalendarProviderInterface.php).
