UPGRADE FROM 1.4 to 1.5
=======================

####AddressBundle:
- `PhoneProvider` class has been added to help getting phone number(s) from object.

####CalendarBundle:
- Added calendar providers. Calendar Provider gives developers a way to add a different kind of items on a calendar. As example developer can use calendar provider to show emails as "Calendar Events" into Calendar.
- Changed REST API for CalendarConnections. Developer should send "CalendarProperty" ID into PUT and DELETE REST methods.
- Added "context menu" for calendar that perform any action with calendar, based on menu from NavigationBundle. "Context menu" can be extend in any other bundle

####EntityExtendBundle:
- `Tools\ExtendConfigDumper` constant `ENTITY` has been deprecated
- Naming of proxy classes for extended entities has been changed to fix naming conflicts
- Adding of extended fields to form has been changed. From now `form.additional` is not available in TWIG template, because extended fields are added to main form and have  `extra_field` flag. The following statement can be used to loop through extended fields in TWIG template: `{% for child in form.children if child.vars.extra_field is defined and child.vars.extra_field %}`.

####FormBundle:
- Added `oro_simple_color_picker` Symfony2 form type based on `hidden` and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff and [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.
- Added `oro_simple_color_choice` Symfony2 form type based on `choice` and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff.
- Added `oro_color_table` Symfony2 form type intended to edit any color in a list and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff and [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.

####UIBundle:
- Added [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff.
- Added [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.
