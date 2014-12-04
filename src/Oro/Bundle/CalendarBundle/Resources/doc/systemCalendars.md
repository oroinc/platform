System Calendars
================

Table of content
-----------------
- [Overview](#overview)
- [Configuration](#configuration)
- [Implementation](#implementation)

##Overview

System calendars can be used to provide common events for all users, for example they can be used for employees' birthdays, company weekends, vacations, etc. These calendars might be made available in scope of a single organization or be shared across all organizations in the system. To manage system calendars go to *System > System Calendar* section. Since a system calendar is created it will be visible in for all users. The visibility organization wide calendars can be restricted by ACL, against system wide calendars which are always visible.

##Configuration

By default both organization and system wide calendars are enabled, but you can easy disable any of them in `app/config.yml`. Just add `enabled_system_calendar` option as it is shown in the following example:

``` yml
oro_calendar:
    enabled_system_calendar: false
```

The possible values of this option:

- **true** - both organization and system wide calendars are enabled
- **false** - both organization and system wide calendars are disabled
- **organization** - only organization wide calendars are enabled
- **system** - only system wide calendars are enabled

##Implementation

The list of system calendars are stored on [oro_system_calendar](../../Entity/SystemCalendar.php) table. Please pay attention on **public** field. It is used to indicate whether a calendar is organization or system wide. System wide calendars are marked as public.

Both organization and system wide calendars are implemented as [calendar providers](provider.md). You can see implementation details in source code:

- [SystemCalendarProvider](../../Provider/SystemCalendarProvider.php) - responsible for **organization wide calendars**
- [PublicCalendarProvider](../../Provider/PublicCalendarProvider.php) - responsible for **system wide calendars**
