UPGRADE FROM 1.4 to 1.5
=======================

####AddressBundle:
- `PhoneProvider` class has been added to help getting phone number(s) from object.

####EntityExtendBundle:
- `Tools\ExtendConfigDumper` constant `ENTITY` has been deprecated
- Naming of proxy classes for extended entities has been changed to fix naming conflicts
- Adding of extended fields to form has been changed. From now `form.additional` is not available in TWIG template, because extended fields are added to main form and have  `extra_field` flag. The following statement can be used to loop through extended fields in TWIG template: `{% for child in form.children if child.vars.extra_field is defined and child.vars.extra_field %}`.

####FormBundle:
- Added `oro_simple_color_picker` Symfony2 form type using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff.

####UIBundle:
- Added [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff.
